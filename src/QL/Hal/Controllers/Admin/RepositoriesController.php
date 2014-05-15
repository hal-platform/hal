<?php

namespace QL\Hal\Controllers\Admin;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Repositories Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class RepositoriesController
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
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param RepositoryRepository $repoRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        RepositoryRepository $repoRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->repoRepo = $repoRepo;
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
        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'repos' => $this->repoRepo->findAll()
                ]
            )
        );
    }
}
