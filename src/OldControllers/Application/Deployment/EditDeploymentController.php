<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\Deployment;

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
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $credentialRepo;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Deployment
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
     * @inheritDoc
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

                'cd_name' => $this->request->post('cd_name'),
                'cd_group' => $this->request->post('cd_group'),
                'cd_config' => $this->request->post('cd_config'),

                'eb_name' => $this->request->post('eb_name'),
                'eb_environment' => $this->request->post('eb_environment'),

                's3_bucket' => $this->request->post('s3_bucket'),
                's3_file' => $this->request->post('s3_file'),

                'script_context' => $this->request->post('script_context'),

                'url' => $this->request->post('url'),
                'credential' => $this->request->post('credential'),
            ];
        } else {
            $form = [
                'name' => $this->deployment->name(),
                'path' => $this->deployment->path(),

                'cd_name' => $this->deployment->cdName(),
                'cd_group' => $this->deployment->cdGroup(),
                'cd_config' => $this->deployment->cdConfiguration(),

                'eb_name' => $this->deployment->ebName(),
                'eb_environment' => $this->deployment->ebEnvironment(),

                's3_bucket' => $this->deployment->s3bucket(),
                's3_file' => $this->deployment->s3file(),

                'script_context' => $this->deployment->scriptContext(),

                'url' => $this->deployment->url(),
                'credential' => $this->deployment->credential() ? $this->deployment->credential()->id() : '',
            ];
        }

        return $form;
    }
}