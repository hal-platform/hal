<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserSettings;
use QL\Hal\Core\Repository\ApplicationRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationsController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var ApplicationRepository
     */
    private $applicationRepo;

    /**
     * @var EntityRepository
     */
    private $groupRepo;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param User $currentUser
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, User $currentUser)
    {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->groupRepo = $em->getRepository(Group::CLASS);

        $this->currentUser = $currentUser;
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

        $favorites = $this->findFavorites($grouped);

        $this->template->render([
            'favorites' => $favorites,
            'applications' => $grouped,
            'groups' => $groups
        ]);
    }

    /**
     * @param array $grouped
     *
     * @return Application[]
     */
    private function findFavorites(array $grouped)
    {
        if (!$settings = $this->currentUser->settings()) {
            return [];
        }

        $saved = array_fill_keys($settings->favoriteApplications(), true);
        $favorites = [];

        foreach ($grouped as $applications) {
            foreach ($applications as $application) {
                if (isset($saved[$application->id()])) {
                    $favorites[] = $application;
                }
            }
        }

        return $favorites;
    }
}
