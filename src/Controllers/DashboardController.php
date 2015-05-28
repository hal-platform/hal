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
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\NewPermissionsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

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
     * @type NewPermissionsService
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
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param PermissionsService $permissions
     * @param Clock $clock
     * @param EntityManagerInterface $em
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser,
        PermissionsService $permissions,
        Clock $clock,
        EntityManagerInterface $em,
        Response $response
    ) {
        $this->template = $template;
        $this->currentUser = $currentUser;
        $this->permissions = $permissions;
        $this->clock = $clock;

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->userRepo = $em->getRepository(User::CLASS);

        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $user = $this->userRepo->find($this->currentUser->getId());

        $recentBuilds = $this->buildRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);
        $recentPushes = $this->pushRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);

        $pending = $this->getAllPendingJobs();

        $stuck = [];
        if ($this->permissions->getUserPermissions($user)->isSuper()) {
            $stuck = $this->getStuckJobs();
        }

        $rendered = $this->template->render([
            'repositories' => $this->permissions->userRepositories($this->currentUser),
            'pending' => $pending,
            'stuck' => $stuck,
            'builds' => $recentBuilds,
            'pushes' => $recentPushes
        ]);

        $this->response->setBody($rendered);
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
