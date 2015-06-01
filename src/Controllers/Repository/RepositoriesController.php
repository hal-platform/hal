<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
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
    private $applicationRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $groups = $this->groupRepo->findBy([], ['name' => 'ASC']);
        $applications = $this->applicationRepo->findBy([], ['name' => 'ASC']);
        usort($applications, $this->appSorter());

        $grouped = [];

        foreach ($applications as $repo) {
            $id = $repo->group()->id();
            if (!isset($grouped[$id])) {
                $grouped[$id] = [];
            }

            $grouped[$id][] = $repo;
        }

        $this->template->render([
            'groups' => $groups,
            'repositories' => $grouped
        ]);
    }

    /**
     * @return Closure
     */
    private function appSorter()
    {
        return function($a, $b) {
            return strcasecmp($a->name(), $b->name());
        };
    }
}
