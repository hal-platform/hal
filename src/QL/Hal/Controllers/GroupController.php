<?php

namespace QL\Hal\Controllers;

use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;

/**
 *  Group Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class GroupController
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
     *  @var GroupRepository
     */
    private $groupRepo;

    /**
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param GroupRepository $groupRepo
     *  @param RepositoryRepository $repoRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->groupRepo = $groupRepo;
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
        $group = $this->groupRepo->findOneBy(['key' => $params['key']]);
        $repos = $this->repoRepo->findBy(['group' => $group]);

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'group' => $group,
                    'repos' => $repos
                ]
            )
        );
    }
}
