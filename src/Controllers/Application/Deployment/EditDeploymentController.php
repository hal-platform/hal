<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditDeploymentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $credentialRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Deployment
     */
    private $deployment;

    /**
     * @param TemplateInterface $template
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Deployment $deployment
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        EntityManagerInterface $em,
        Deployment $deployment
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->credentialRepo = $em->getRepository(Credential::CLASS);
        $this->deployment = $deployment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $renderContext = [
            'form' => $this->data(),
            'deployment' => $this->deployment,
            'credentials' => $this->credentialRepo->findBy([], ['name' => 'ASC'])
        ];

        $this->template->render($renderContext);
    }

    /**
     * @return array
     */
    private function data()
    {
        if ($this->request->isPost()) {
            $form = [
                'name' => $this->request->post('name'),
                'path' => $this->request->post('path'),

                'eb_environment' => $this->request->post('eb_environment'),
                'ec2_pool' => $this->request->post('ec2_pool'),
                's3_bucket' => $this->request->post('s3_bucket'),
                's3_file' => $this->request->post('s3_file'),

                'url' => $this->request->post('url'),
                'credential' => $this->request->post('credential'),
            ];
        } else {
            $form = [
                'name' => $this->deployment->name(),
                'path' => $this->deployment->path(),

                'eb_environment' => $this->deployment->ebEnvironment(),
                'ec2_pool' => $this->deployment->ec2Pool(),

                's3_bucket' => $this->deployment->s3bucket(),
                's3_file' => $this->deployment->s3file(),

                'url' => $this->deployment->url(),
                'credential' => $this->deployment->credential() ? $this->deployment->credential()->id() : '',
            ];
        }

        return $form;
    }
}
