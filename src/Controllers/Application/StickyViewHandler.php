<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Flasher;
use QL\Hal\Service\StickyViewService;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;
use Slim\Http\Response;

class StickyViewHandler implements MiddlewareInterface
{
    /**
     * @type EntityRepository
     */
    private $viewRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type StickyViewService
     */
    private $stickyView;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param Flasher $flasher
     * @param StickyViewService $stickyView
     *
     * @param Response $response
     * @param Json $json
     *
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        EntityManagerInterface $em,
        Request $request,
        Flasher $flasher,
        StickyViewService $stickyView,

        Response $response,
        Json $json,

        Application $application,
        Environment $environment
    ) {
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);

        $this->request = $request;
        $this->flasher = $flasher;
        $this->stickyView = $stickyView;

        // For JSON processing
        $this->response = $response;
        $this->json = $json;

        $this->application = $application;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $isAjax = ($this->request->getMediaType() === 'application/json');

        if ($isAjax) {
            return $this->handleJSONForm();
        }

        $this->saveStickyView($this->request->post('view'));
        $this->flasher->load('application.status', ['application' => $this->application->id()]);
    }

    /**
     * @param string $viewID
     *
     * @return void
     */
    private function saveStickyView($viewID)
    {
        $saved = null;

        if ($viewID) {
            $view = $this->viewRepo->findOneBy([
                'id' => $viewID,
                'application' => $this->application,
                'environment' => $this->environment
            ]);

            $saved = $view->id();
        }

        $this->stickyView->save($this->application->id(), $this->environment->id(), $saved);
    }

    /**
     * @return void
     */
    private function handleJSONForm()
    {
        $this->response->headers->set('Content-Type', 'application/json');

        $decoded = call_user_func($this->json, $this->request->getBody());
        if (is_array($decoded) && isset($decoded['view'])) {
            $view = $decoded['view'];
        } else {
            $view = null;
        }

        $this->saveStickyView($view);

        $this->response->setBody($this->json->encode([
            'awk' => 'cool story bro',
        ]));
    }
}
