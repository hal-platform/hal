<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
     * @type Server
     */
    private $server;

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
     * @param Server $server
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ServerValidator $validator,
        Flasher $flasher,
        Request $request,
        Server $server
    ) {
        $this->template = $template;

        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->validator = $validator;
        $this->flasher = $flasher;
        $this->request = $request;
        $this->server = $server;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $form = $this->data();

        if ($modified = $this->handleForm($form)) {
            return $this->flasher
                ->withFlash(self::SUCCESS, 'success')
                ->load('server', ['server' => $modified->id()]);
        }

        $context = [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'server' => $this->server,
            'environments' => $this->envRepo->getAllEnvironmentsSorted(),
        ];

        $this->template->render($context);
    }

    /**
     * @param array $data
     *
     * @return Server|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $server = $this->validator->isEditValid(
            $this->server,
            $data['server_type'],
            $data['environment'],
            $data['hostname'],
            $data['region']
        );

        if ($server) {
            // persist to database
            $this->em->merge($server);
            $this->em->flush();
        }

        return $server;
    }

    /**
     * @return array
     */
    private function data()
    {
        if ($this->request->isPost()) {
            $form = [
                'server_type' => $this->request->post('server_type'),
                'environment' => $this->request->post('environment'),

                'hostname' => trim($this->request->post('hostname')),
                'region' => trim($this->request->post('region'))
            ];
        } else {
            $form = [
                'server_type' => $this->server->type(),
                'environment' => $this->server->environment()->id(),

                'hostname' => $this->server->name(),
                'region' => $this->server->name(),
            ];
        }

        return $form;
    }
}
