<?php

namespace QL\Hal\Controllers\Group;

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
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$group = $this->groupRepo->find($params['id'])) {
            return $notFound();
        }

        $rendered = $this->layout->render($this->template, [
            'group' => $group,
            'repos' => $this->repoRepo->findBy(['group' => $group])
        ]);

        $response->body($rendered);
    }
}
