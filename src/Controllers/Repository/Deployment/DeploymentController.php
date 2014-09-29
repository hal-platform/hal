<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class DeploymentController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    /**
     *  @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param DeploymentRepository $deploymentRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        DeploymentRepository $deploymentRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->deploymentRepo = $deploymentRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$deployment = $this->deploymentRepo->find($params['id'])) {
            return $notFound();
        }

        $rendered = $this->layout->render($this->template, [
            'deployment' => $deployment
        ]);

        $response->body($rendered);
    }
}
