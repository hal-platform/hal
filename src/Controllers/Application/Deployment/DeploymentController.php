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

class DeploymentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Deployment
     */
    private $deployment;

    /**
     * @param TemplateInterface $template
     * @param Deployment $deployment
     */
    public function __construct(
        TemplateInterface $template,
        Deployment $deployment
    ) {
        $this->template = $template;

        $this->deployment = $deployment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // $d = $this->deployment->credential()->id();

        $this->template->render([
            'deployment' => $this->deployment
        ]);
    }
}
