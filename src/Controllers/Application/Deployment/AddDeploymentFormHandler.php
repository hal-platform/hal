<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Flasher;
use QL\Hal\Validator\DeploymentValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use Slim\Http\Request;

class AddDeploymentFormHandler implements MiddlewareInterface
{
    const SUCCESS = 'Deployment added.';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var DeploymentValidator
     */
    private $validator;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

    /**
     * @param EntityManager $em
     * @param DeploymentValidator $validator
     * @param Flasher $flasher
     * @param Context $context
     * @param Request $request
     * @param Application $application
     */
    public function __construct(
        EntityManager $em,
        DeploymentValidator $validator,
        Flasher $flasher,
        Context $context,
        Request $request,
        Application $application
    ) {
        $this->em = $em;
        $this->validator = $validator;

        $this->flasher = $flasher;
        $this->context = $context;

        $this->request = $request;
        $this->application = $application;

        $this->envRepo = $em->getRepository(Environment::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $deployment = $this->validator->isValid(
            $this->application,
            $this->request->post('server'),
            $this->request->post('name') ?: '',
            $this->request->post('path'),

            $this->request->post('cd_name'),
            $this->request->post('cd_group'),
            $this->request->post('cd_config'),

            $this->request->post('eb_name'),
            $this->request->post('eb_environment'),

            $this->request->post('s3_bucket'),
            $this->request->post('s3_file'),

            $this->request->post('url') ?: ''
        );

        // if validator didn't create a deployment, pass through to controller to handle errors
        if (!$deployment) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        // Clear cached query for buildable environments
        $this->envRepo->clearBuildableEnvironmentsByApplication($deployment->application());

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        // flash and redirect
        $this->flasher
            ->withFlash(self::SUCCESS, 'success')
            ->load('deployments', ['application' => $this->application->id()]);
    }
}
