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
use Slim\Http\Request;

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
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        EntityManagerInterface $em,
        Request $request,
        Flasher $flasher,
        StickyViewService $stickyView,
        Application $application,
        Environment $environment
    ) {
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);

        $this->request = $request;
        $this->flasher = $flasher;
        $this->stickyView = $stickyView;

        $this->application = $application;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $saved = null;

        if ($viewID = $this->request->post('view')) {
            $view = $this->viewRepo->findOneBy([
                'id' => $viewID,
                'application' => $this->application,
                'environment' => $this->environment
            ]);

            $saved = $view->id();
        }

        $this->stickyView->save($this->application->id(), $this->environment->id(), $saved);

        $this->flasher->load('application.status', ['application' => $this->application->id()]);
    }
}
