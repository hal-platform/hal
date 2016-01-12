<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use QL\Hal\Core\Entity\Deployment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeploymentController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Deployment
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
