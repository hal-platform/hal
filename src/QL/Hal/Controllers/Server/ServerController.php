<?php

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Server Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class ServerController
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
     *  @var DeploymentRepository
     */
    private $deployRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param ServerRepository $serverRepo
     *  @param DeploymentRepository $deployRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        ServerRepository $serverRepo,
        DeploymentRepository $deployRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->serverRepo = $serverRepo;
        $this->deployRepo = $deployRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $server = $this->serverRepo->findOneBy(['name' => $params['server']]);

        if (!$server) {
            call_user_func($notFound);
            return;
        }

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'server' => $server,
                    'deployments' => $this->deployRepo->findBy(['server' => $server])
                ]
            )
        );
    }
}

