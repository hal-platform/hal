<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Flasher;
use QL\Hal\Validator\DeploymentValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use Slim\Http\Request;

class EditDeploymentHandler implements MiddlewareInterface
{
    const EDIT_SUCCESS = 'Deployment updated.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type DeploymentValidator
     */
    private $validator;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Context
     */
    private $context;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Deployment
     */
    private $deployment;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param DeploymentValidator $validator
     * @param Flasher $flasher
     * @param Context $context
     * @param Request $request
     * @param Deployment $deployment
     */
    public function __construct(
        EntityManagerInterface $em,
        DeploymentValidator $validator,
        Flasher $flasher,
        Context $context,
        Request $request,
        Deployment $deployment
    ) {
        $this->em = $em;
        $this->validator = $validator;

        $this->flasher = $flasher;
        $this->context = $context;

        $this->request = $request;
        $this->deployment = $deployment;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $path = $this->request->post('path');
        $ebEnvironment = $this->request->post('eb_environment');
        $ec2Pool = $this->request->post('ec2_pool');
        $url = $this->request->post('url');

        $deployment = $this->validator->isEditValid($this->deployment, $path, $ebEnvironment, $ec2Pool, $url);
        if (!$deployment) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        // Wipe eb, ec2  for RSYNC server
        // Wipe path, ec2 for EB servers
        // Wipe path, eb  for EC2 server
        $serverType = $deployment->server()->type();
        if ($serverType === ServerEnum::TYPE_RSYNC) {
            $ebEnvironment = $ec2Pool = null;

        } else if ($serverType === ServerEnum::TYPE_EC2) {
            $path = $ebEnvironment = null;

        } else if ($serverType === ServerEnum::TYPE_EB) {
            $ec2Pool = null;
        }

        $deployment->withPath($path);
        $deployment->withEbEnvironment($ebEnvironment);
        $deployment->withEc2Pool($ec2Pool);
        $deployment->withUrl(HttpUrl::create($url));

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        $this->flasher
            ->withFlash(self::EDIT_SUCCESS, 'success')
            ->load('deployment', ['application' => $deployment->application()->id(), 'deployment' => $deployment->id()]);
    }
}
