<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository\ApplicationRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationsController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type ApplicationRepository
     */
    private $applicationRepo;

    /**
     * @type EntityRepository
     */
    private $groupRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->groupRepo = $em->getRepository(Group::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $grouped = $this->applicationRepo->getGroupedApplications();

        $groups = [];
        foreach ($this->groupRepo->findAll() as $group) {
            $groups[$group->id()] = $group;
        }

        $this->template->render([
            'applications' => $grouped,
            'groups' => $groups
        ]);
    }
}
