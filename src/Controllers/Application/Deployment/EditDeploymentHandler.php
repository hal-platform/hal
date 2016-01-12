<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
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
     * @var EntityManagerInterface
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
     * @var Deployment
     */
    private $deployment;

    /**
     * @var array
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

        $form = $this->data();

        $deployment = $this->validator->isEditValid(
            $this->deployment,
            $form['name'],
            $form['path'],
            $form['cd_name'],
            $form['cd_group'],
            $form['cd_config'],
            $form['eb_name'],
            $form['eb_environment'],
            $form['ec2_pool'],
            $form['s3_bucket'],
            $form['s3_file'],
            $form['url'],
            $form['credential']
        );

        if (!$deployment) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        $this->em->persist($deployment);
        $this->em->flush();

        $this->flasher
            ->withFlash(self::EDIT_SUCCESS, 'success')
            ->load('deployment', ['application' => $deployment->application()->id(), 'deployment' => $deployment->id()]);
    }

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'name' => $this->request->post('name'),
            'path' => $this->request->post('path'),

            'cd_name' => $this->request->post('cd_name'),
            'cd_group' => $this->request->post('cd_group'),
            'cd_config' => $this->request->post('cd_config'),

            'eb_name' => $this->request->post('eb_name'),
            'eb_environment' => $this->request->post('eb_environment'),

            'ec2_pool' => $this->request->post('ec2_pool'),
            's3_bucket' => $this->request->post('s3_bucket'),
            's3_file' => $this->request->post('s3_file'),

            'url' => $this->request->post('url'),
            'credential' => $this->request->post('credential'),
        ];

        return $form;
    }
}
