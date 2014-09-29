<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class EnvironmentsController
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
     *  @var EnvironmentRepository
     */
    private $envRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EnvironmentRepository $envRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EnvironmentRepository $envRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->envRepo = $envRepo;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $rendered = $this->layout->render($this->template, [
            'envs' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ]);

        $response->body($rendered);
    }
}
