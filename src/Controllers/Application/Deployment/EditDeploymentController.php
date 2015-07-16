<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

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
    private $applicationRepo;
    private $deploymentRepo;

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
     * @param Deployment $deployment
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Deployment $deployment
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->deployment = $deployment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $renderContext = [
            'form' => [
                'path' => $this->request->isPost() ? $this->request->post('path') : $this->deployment->path(),
                'eb_environment' => $this->request->isPost() ? $this->request->post('eb_environment') : $this->deployment->ebEnvironment(),
                'ec2_pool' => $this->request->isPost() ? $this->request->post('ec2_pool') : $this->deployment->ec2Pool(),
                'url' => $this->request->isPost() ? $this->request->post('url') : $this->deployment->url(),
            ],
            'deployment' => $this->deployment
        ];

        $this->template->render($renderContext);
    }
}
