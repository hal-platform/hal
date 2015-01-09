<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class GroupsController implements ControllerInterface
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
        $rendered = $this->template->render([
            'groups' => $this->getGroupsWithRepositories()
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @return array
     */
    private function getGroupsWithRepositories()
    {
        $data = [];

        $groups = $this->groupRepo->findBy([], ['name' => 'ASC']);
        foreach ($groups as $group) {
            $data[] = [
                'group' => $group,
                'repositories' => $this->sortGroupRepositories($group)
            ];
        }

        return $data;
    }

    /**
     * @param Group $group
     * @return Repository[]
     */
    private function sortGroupRepositories(Group $group)
    {
        $repos = $group->getRepositories()->toArray();
        $sorter = function($a, $b) {
            return strcasecmp($a->getDescription(), $b->getDescription());
        };

        usort($repos, $sorter);

        return $repos;
    }
}
