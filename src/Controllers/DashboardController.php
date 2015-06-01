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
use MCP\DataType\Time\Clock;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class DashboardController implements ControllerInterface
{
    use CachingTrait;

    const CACHE_KEY_PERMISSION_APPLICATIONS = 'page:db.job_counts.%s';
    const AGE_OF_STUCK_JOBS = '-45 minutes';
    const AGE_OF_RECENT_BUILDS = '-2 months';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @type BuildRepository
     */
    private $buildRepo;
    private $pushRepo;
    private $userRepo;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param PermissionsService $permissions
     * @param Clock $clock
     * @param EntityManagerInterface $em
     * @param Json $json
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser,
        PermissionsService $permissions,
        Clock $clock,
        EntityManagerInterface $em,
        Json $json
    ) {
        $this->template = $template;
        $this->currentUser = $currentUser;
        $this->permissions = $permissions;
        $this->clock = $clock;

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->userRepo = $em->getRepository(User::CLASS);

        $this->json = $json;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $user = $this->userRepo->find($this->currentUser->id());

        $recentBuilds = $this->buildRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);
        $recentPushes = $this->pushRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);

        $pending = $this->getAllPendingJobs();

        $stuck = [];
        if ($this->permissions->getUserPermissions($user)->isSuper()) {
            $stuck = $this->getStuckJobs();
        }

        $this->template->render([
            'recent_applications' => $this->getBuildableApplications(),
            'pending' => $pending,
            'stuck' => $stuck,
            'builds' => $recentBuilds,
            'pushes' => $recentPushes
        ]);
    }

    /**
     * @return array
     */
    private function getBuildableApplications()
    {
        $key = sprintf(self::CACHE_KEY_PERMISSION_APPLICATIONS, $this->currentUser->id());

        // external cache
        if ($result = $this->getFromCache($key)) {
            $decoded = $this->json->decode($result);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $now = $this->clock->read();
        $twoMonthsAgo = $now->modify(self::AGE_OF_RECENT_BUILDS);
        $recentApplications = $this->userRepo->getUsersRecentApplications($this->currentUser, $twoMonthsAgo);

        $data = [];
        foreach ($recentApplications as $app) {
            $data[$app->getId()] = $app->getName();
        }

        uasort($data, function($a, $b) {
            return strcasecmp($a, $b);
        });

        $this->setToCache($key, $this->json->encode($data));
        return $data;
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
            $a = $aEntity->getCreated();
            $b = $bEntity->getCreated();

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
}
