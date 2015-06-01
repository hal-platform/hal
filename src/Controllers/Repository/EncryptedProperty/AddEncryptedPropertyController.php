<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddEncryptedPropertyController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     *
     * @param EntityManagerInterface $em
     *
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);

        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$application = $this->applicationRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        if (!$environments = $this->envRepo->getAllEnvironmentsSorted()) {
            // who cares. I dont really want to deal with redirecting
            return call_user_func($this->notFound);
        }

        $this->template->render([
            'form' => [
                'environment' => $this->request->post('environment'),
                'name' => $this->request->post('name'),
                'decrypted' => $this->request->post('decrypted')
            ],
            'repository' => $application,
            'environments' => $environments
        ]);
    }
}
