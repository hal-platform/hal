<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Repository\GroupRepository;
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
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param GroupRepository $groupRepo
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, GroupRepository $groupRepo, Response $response)
    {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $groups = $this->groupRepo->findBy([], ['name' => 'ASC']);

        $repositories = [];
        $repoSort = $this->repoSorter();

        foreach ($groups as $group) {
            $repos = $group->getRepositories()->toArray();
            usort($repos, $repoSort);
            $repositories[$group->getId()] = $repos;
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
