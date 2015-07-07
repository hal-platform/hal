<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Services\ElasticBeanstalkService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeploymentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type ElasticBeanstalkService
     */
    private $ebService;

    /**
     * @type Deployment
     */
    private $deployment;

    /**
     * @param TemplateInterface $template
     * @param ElasticBeanstalkService $ebService
     * @param Deployment $deployment
     */
    public function __construct(
        TemplateInterface $template,
        ElasticBeanstalkService $ebService,
        Deployment $deployment
    ) {
        $this->template = $template;
        $this->ebService = $ebService;

        $this->deployment = $deployment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $ebEnv = null;
        if ($this->deployment->ebEnvironment()) {
            if ($envs = $this->ebService->getEnvironmentsByDeployments($this->deployment)) {
                $ebEnv = array_pop($envs);
            }
        }

        $this->template->render([
            'deployment' => $this->deployment,
            'eb_environment' => $ebEnv
        ]);
    }
}
