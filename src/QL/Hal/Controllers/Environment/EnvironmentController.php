<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class EnvironmentController
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
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EnvironmentRepository $envRepo
     *  @param ServerRepository $serverRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EnvironmentRepository $envRepo,
        ServerRepository $serverRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->envRepo = $envRepo;
        $this->serverRepo = $serverRepo;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$environment = $this->envRepo->find($params['id'])) {
            return $notFound();
        }

        $rendered = $this->layout->render($this->template, [
            'env' => $environment,
            'servers' => $this->serverRepo->findBy(['environment' => $environment])
        ]);

        $response->body($rendered);
    }
}
