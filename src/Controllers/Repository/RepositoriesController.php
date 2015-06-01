<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RepositoriesController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $groupRepo;
    private $repoRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->repoRepo = $em->getRepository(Repository::CLASS);
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

        $this->template->render([
            'groups' => $groups,
            'repositories' => $repositories
        ]);
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
