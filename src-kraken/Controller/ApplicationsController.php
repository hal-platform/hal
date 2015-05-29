<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
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
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $repository;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param Response $response
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        EntityManagerInterface $em,
        Flasher $flasher
    ) {
        $this->request = $request;
        $this->template = $template;

        $this->em = $em;
        $this->repository = $this->em->getRepository(Application::CLASS);

        $this->flasher = $flasher;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $context = [];

        if ($this->request->isPost()) {
            if ($application = $this->handleForm()) {
                return $this->flasher
                    ->withFlash(self::SUCCESS, 'success')
                    ->load('kraken.applications');
            }

            $context = [
                'errors' => $this->errors,
                'form' => [
                    'id' => $this->request->post('id')
                ]
            ];
        }

        $apps = $this->repository->findBy([], ['name' => 'ASC']);

        $context['applications'] = $apps;

        $this->template->render($context);
    }

    /**
     * @return Application|null
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

        return $this->saveApplication($id);
    }

    /**
     * @param string $id
     *
     * @return Application
     */
    private function saveApplication($id)
    {
        $application = (new Application)
            ->withId($id);

        // persist to database
        $this->em->persist($application);
        $this->em->flush();

        return $application;
    }
}
