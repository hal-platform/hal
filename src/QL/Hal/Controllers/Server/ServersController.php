<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Servers Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class ServersController
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
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param ServerRepository $serverRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        ServerRepository $serverRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->serverRepo = $serverRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $rendered = $this->layout->render($this->template, [
            'servers' => $this->serverRepo->findBy([], ['environment' => 'ASC', 'name' => 'ASC'])
        ]);

        $response->body($rendered);
    }
}
