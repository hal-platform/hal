<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Repository\GroupRepository;
use QL\Hal\Core\Repository\RepositoryRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class RepositoriesController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        Response $response
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $groups = $this->groupRepo->findBy([], ['name' => 'ASC']);
        $repos = $this->repoRepo->findBy([], ['name' => 'ASC']);
        usort($repos, $this->repoSorter());

        $repositories = [];

        foreach ($repos as $repo) {
            $id = $repo->getGroup()->getId();
            if (!isset($repositories[$id])) {
                $repositories[$id] = [];
            }

            $repositories[$id][] = $repo;
        }

        $rendered = $this->template->render([
            'groups' => $groups,
            'repositories' => $repositories
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @return Closure
     */
    private function repoSorter()
    {
        return function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        };
    }
}
