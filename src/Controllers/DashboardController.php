<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionService;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DashboardController implements ControllerInterface
{
    const AGE_OF_STUCK_JOBS = '-45 minutes';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @type EntityRepository
     */
    private $buildRepo;
    private $pushRepo;
    private $appRepo;

    /**
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param PermissionService $permissions
     * @param Clock $clock
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser,
        PermissionService $permissions,
        Clock $clock,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->currentUser = $currentUser;
        $this->permissions = $permissions;
        $this->clock = $clock;

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->appRepo = $em->getRepository(Application::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $recentBuilds = $this->buildRepo->findBy(['user' => $this->currentUser], ['created' => 'DESC'], 5);
        $recentPushes = $this->pushRepo->findBy(['user' => $this->currentUser], ['created' => 'DESC'], 5);

        $pending = $this->getAllPendingJobs();

        $stuck = [];
        if ($this->permissions->getUserPermissions($this->currentUser)->isSuper()) {
            $stuck = $this->getStuckJobs();
        }

        $this->template->render([
            'favorites' => $this->findFavorites(),
            'pending' => $pending,
            'stuck' => $stuck,
            'builds' => $recentBuilds,
            'pushes' => $recentPushes
        ]);
    }

    /**
     * @return array
     */
    private function getAllPendingJobs()
    {
        $buildCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Building'))
            ->orderBy(['created' => 'DESC']);

        $pushCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Pushing'))
            ->orderBy(['created' => 'DESC']);

        $builds = $this->buildRepo->matching($buildCriteria);
        $pushes = $this->pushRepo->matching($pushCriteria);

        $jobs = array_merge($builds->toArray(), $pushes->toArray());
        usort($jobs, $this->queueSort());

        return $jobs;
    }

    /**
     * @return array
     */
    private function getStuckJobs()
    {
        $now = $this->clock->read();
        $thirtyMinutesAgo = $now->modify(self::AGE_OF_STUCK_JOBS);

        $buildCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Building'))
            ->andWhere(Criteria::expr()->lt('created', $thirtyMinutesAgo))
            ->orderBy(['created' => 'DESC']);

        $pushCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Pushing'))
            ->andWhere(Criteria::expr()->lt('created', $thirtyMinutesAgo))
            ->orderBy(['created' => 'DESC']);

        $builds = $this->buildRepo->matching($buildCriteria);
        $pushes = $this->pushRepo->matching($pushCriteria);

        $jobs = array_merge($builds->toArray(), $pushes->toArray());

        return $jobs;
    }

    /**
     * @return Closure
     */
    private function queueSort()
    {
        return function($aEntity, $bEntity) {
            $a = $aEntity->created();
            $b = $bEntity->created();

            if ($a == $b) {
                return 0;
            }

            // If missing created time, move to bottom
            if ($a === null xor $b === null) {
                return ($a === null) ? 1 : 0;
            }

            if ($a < $b) {
                return 1;
            }

            return -1;
        };
    }


    /**
     * @return Application[]
     */
    private function findFavorites()
    {
        if (!$settings = $this->currentUser->settings()) {
            return [];
        }

        if (!$fav = $settings->favoriteApplications()) {
            return [];
        }

        $criteria = (new Criteria)
            ->where(Criteria::expr()->in('id', $fav));

        $apps = $this->appRepo->matching($criteria);

        return $apps->toArray();
    }
}
