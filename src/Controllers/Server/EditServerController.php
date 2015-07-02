<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Flasher;
use QL\Hal\Validator\ServerValidator;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditServerController implements ControllerInterface
{
    const SUCCESS = 'Server updated successfully.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Validator
     */
    private $validator;

    /**
     * @type Flasher
     */
    private $flasher;

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
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ServerValidator $validator,
        Flasher $flasher,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->validator = $validator;
        $this->flasher = $flasher;
        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$server = $this->serverRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($this->request->isPost()) {
            $form = [
                'server_type' => $this->request->post('server_type'),
                'environment' => $this->request->post('environment'),
                'hostname' => $this->request->post('hostname')
            ];
        } else {
            $form = [
                'server_type' => $server->type(),
                'environment' => $server->environment()->id(),
                'hostname' => $server->name(),
            ];
        }

        if ($this->request->isPost()) {
            if ($modified = $this->handleForm($server, $form)) {
                // flash and redirect
                return $this->flasher
                    ->withFlash(self::SUCCESS, 'success')
                    ->load('server', ['id' => $modified->id()]);
            }
        }

        $context = [
            'form' => $form,
            'errors' => $this->validator->errors(),
            'server' => $server,
            'environments' => $this->envRepo->getAllEnvironmentsSorted(),
        ];

        $this->template->render($context);
    }

    /**
     * @param Server $server
     * @param array $data
     *
     * @return Server|null
     */
    private function handleForm(Server $server, array $data)
    {
        $server = $this->validator->isEditValid(
            $server,
            $data['server_type'],
            $data['environment'],
            $data['hostname']
        );

        if ($server) {
            // persist to database
            $this->em->merge($server);
            $this->em->flush();
        }

        return $server;
    }
}
