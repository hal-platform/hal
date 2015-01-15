<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManager;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Session;
use QL\Hal\Validator\DeploymentValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class EditDeploymentHandler implements MiddlewareInterface
{
    const EDIT_SUCCESS = 'Deployment updated.';
    const TYPE_RSYNC = 'rsync';
    const TYPE_EBS = 'elasticbeanstalk';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type DeploymentRepository
     */
    private $repository;

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
     * @param EntityManager $em
     * @param DeploymentRepository $repository
     * @param DeploymentValidator $validator
     * @param Session $session
     * @param Url $url
     * @param Context $context
     * @param Request $request
     * @param array $parameters
     */
    public function __construct(
        EntityManager $em,
        DeploymentRepository $repository,
        DeploymentValidator $validator,
        Session $session,
        Url $url,
        Context $context,
        Request $request,
        array $parameters
    ) {
        $this->em = $em;
        $this->repository = $repository;
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
        $deployment = $this->repository->find($this->parameters['id']);
        if (!$deployment) {
            // fall through to controller
            return;
        }

        if (!$this->request->isPost()) {
            return;
        }

        $path = $this->request->post('path');
        $ebsEnvironment = $this->request->post('ebs_environment');
        $url = $this->request->post('url');

        $deployment = $this->validator->isEditValid($deployment, $path, $ebsEnvironment, $url);
        if (!$deployment) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        // Wipe path for EBS servers
        // Wipe ebs for RSYNC server
        $serverType = $deployment->getServer()->getType();
        if ($serverType === self::TYPE_RSYNC) {
            $ebsEnvironment = null;
        } else if ($serverType === self::TYPE_EBS) {
            $path = null;
        }

        $deployment->setPath($path);
        $deployment->setEbsEnvironment($ebsEnvironment);
        $deployment->setUrl(HttpUrl::create($url));

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(self::EDIT_SUCCESS, 'success');
        $this->url->redirectFor('repository.deployments', ['repository' => $this->parameters['repository'], 'id' => $this->parameters['id']], [], 303);
    }
}
