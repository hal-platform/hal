<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use QL\Hal\Session;
use Slim\Http\Request;

class ApplicationsController implements ControllerInterface
{
    const SUCCESS = 'Application added.';
    const ERR_INVALID_ID = 'Invalid ID. Application IDs must be alphanumeric.';
    const ERR_DUPLICATE_ID = 'An application with this ID already exists.';

    const VALIDATE_ID_REGEX = '/^[a-zA-Z0-9]{2,40}$/';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $repository;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param Response $response
     * @param TemplateInterface $template
     * @param Url $url
     * @param Session $session
     * @param $em
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        $em,

        Url $url,
        Session $session
    ) {
        $this->request = $request;
        $this->template = $template;

        $this->em = $em;
        $this->repository = $this->em->getRepository(Application::CLASS);

        $this->url = $url;
        $this->session = $session;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $context = [];

        if ($this->request->isPost()) {
            $this->handleForm();
            $context = [
                'errors' => $this->errors,
                'form' => [
                    'id' => $this->request->post('id')
                ]
            ];
        }

        $apps = $this->repository->findBy([], ['id' => 'ASC']);

        $context['applications'] = $apps;

        $this->template->render($context);
    }

    /**
     * @return void
     */
    private function handleForm()
    {
        $id = $this->request->post('id');

        if (preg_match(self::VALIDATE_ID_REGEX, $id) !== 1) {
            $this->errors[] = self::ERR_INVALID_ID;
        }

        // dupe check
        if (!$this->errors && $dupe = $this->repository->find($id)) {
            $this->errors[] = self::ERR_DUPLICATE_ID;
        }

        if ($this->errors) {
            return null;
        }

        $this->saveApplication($id);
    }

    /**
     * @param string $id
     *
     * @return void
     */
    private function saveApplication($id)
    {
        $application = (new Application)
            ->withId($id);

        // persist to database
        $this->em->persist($application);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(self::SUCCESS, 'success');
        $this->url->redirectFor('kraken.applications');
    }
}
