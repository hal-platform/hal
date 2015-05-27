<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Session;
use QL\Hal\Validator\DeploymentValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class EditDeploymentHandler implements MiddlewareInterface
{
    const EDIT_SUCCESS = 'Deployment updated.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @type DeploymentValidator
     */
    private $validator;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Context
     */
    private $context;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param DeploymentValidator $validator
     * @param Session $session
     * @param Url $url
     * @param Context $context
     * @param Request $request
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        DeploymentValidator $validator,
        Session $session,
        Url $url,
        Context $context,
        Request $request,
        array $parameters
    ) {
        $this->em = $em;
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->validator = $validator;

        $this->session = $session;
        $this->url = $url;
        $this->context = $context;

        $this->request = $request;
        $this->parameters = $parameters;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $deployment = $this->deploymentRepo->find($this->parameters['id']);
        if (!$deployment) {
            // fall through to controller
            return;
        }

        if (!$this->request->isPost()) {
            return;
        }

        $path = $this->request->post('path');
        $ebEnvironment = $this->request->post('eb_environment');
        $ec2Pool = $this->request->post('ec2_pool');
        $url = $this->request->post('url');

        $deployment = $this->validator->isEditValid($deployment, $path, $ebEnvironment, $ec2Pool, $url);
        if (!$deployment) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        // Wipe eb, ec2  for RSYNC server
        // Wipe path, ec2 for EB servers
        // Wipe path, eb  for EC2 server
        $serverType = $deployment->getServer()->getType();
        if ($serverType === ServerEnum::TYPE_RSYNC) {
            $ebEnvironment = $ec2Pool = null;

        } else if ($serverType === ServerEnum::TYPE_EC2) {
            $path = $ebEnvironment = null;

        } else if ($serverType === ServerEnum::TYPE_EB) {
            $ec2Pool = null;
        }

        $deployment->setPath($path);
        $deployment->setEbEnvironment($ebEnvironment);
        $deployment->setEc2Pool($ec2Pool);
        $deployment->setUrl(HttpUrl::create($url));

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(self::EDIT_SUCCESS, 'success');
        $this->url->redirectFor('repository.deployments', ['repository' => $this->parameters['repository'], 'id' => $this->parameters['id']], [], 303);
    }
}
