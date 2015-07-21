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
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddServerController implements ControllerInterface
{
    const SUCCESS = 'Server "%s" added.';

    const ERR_NO_ENVIRONMENTS = 'A server requires an environment. Environments must be added before servers.';

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
     * @type ServerValidator
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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param ServerValidator $validator
     * @param Flasher $flasher
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ServerValidator $validator,
        Flasher $flasher,
        Request $request
    ) {
        $this->template = $template;

        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->validator = $validator;
        $this->flasher = $flasher;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$environments = $this->envRepo->getAllEnvironmentsSorted()) {
            return $this->flasher
                ->withFlash(self::ERR_NO_ENVIRONMENTS, 'error')
                ->load('environment.add');
        }

        $form = $this->data();

        if ($server = $this->handleForm($form)) {

            $name = $server->name();
            if ($server->type() === ServerEnum::TYPE_EB) {
                $name = 'Elastic Beanstalk';
            } elseif ($server->type() === ServerEnum::TYPE_EC2) {
                $name = 'EC2';
            } elseif ($server->type() === ServerEnum::TYPE_S3) {
                $name = 'S3';
            }

            return $this->flasher
                ->withFlash(sprintf(self::SUCCESS, $name), 'success')
                ->load('servers');
        }

        $context = [
            'form' => $form,
            'errors' => $this->validator->errors(),
            'environments' => $environments
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

        $server = $this->validator->isValid(
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
        $form = [
            'server_type' => $this->request->post('server_type'),
            'environment' => $this->request->post('environment'),

            'hostname' => trim($this->request->post('hostname')),
            'region' => trim($this->request->post('region'))
        ];

        return $form;
    }
}
