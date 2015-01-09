<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Session;
use QL\Hal\Validator\AddDeploymentValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class AddDeploymentFormHandler implements MiddlewareInterface
{
    const SUCCESS = 'Deployment added.';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type AddDeploymentValidator
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
     * @param EntityManager $em
     * @param AddDeploymentValidator $validator
     * @param Session $session
     * @param Url $url
     * @param Context $context
     * @param Request $request
     * @param array $parameters
     */
    public function __construct(
        EntityManager $em,
        AddDeploymentValidator $validator,
        Session $session,
        Url $url,
        Context $context,
        Request $request,
        array $parameters
    ) {
        $this->em = $em;
        $this->validator = $validator;

        $this->session = $session;
        $this->url = $url;
        $this->context = $context;

        $this->request = $request;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $deployment = $this->validator->isValid(
            $this->parameters['repository'],
            $this->request->post('server'),
            $this->request->post('path'),
            $this->request->post('url')
        );

        // if validator didn't create a deployment, pass through to controller to handle errors
        if (!$deployment) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(self::SUCCESS, 'success');
        $this->url->redirectFor('repository.deployments', ['repository' => $this->parameters['repository']], [], 303);
    }
}
