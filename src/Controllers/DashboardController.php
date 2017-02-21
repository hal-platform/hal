<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DashboardController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $pushRepo;
    private $appRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param Clock $clock
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PermissionService $permissions,
        Clock $clock
    ) {
        $this->template = $template;
        $this->permissions = $permissions;
        $this->clock = $clock;

        $this->buildRepo = $em->getRepository(Build::class);
        $this->pushRepo = $em->getRepository(Push::class);
        $this->appRepo = $em->getRepository(Application::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $recentBuilds = $recentPushes = $stuck = $favorites = [];

        if ($user = $this->getUser($request)) {
            $recentBuilds = $this->buildRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);
            $recentPushes = $this->pushRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);

            $favorites = $this->findFavorites($user);

            // Only supers get stuck jobs list
            $isSuper = $this->permissions->getUserPermissions($user)->isSuper();
            $stuck = $isSuper ? $this->getStuckJobs() : [];
        }

        return $this->withTemplate($request, $response, $this->template, [
            'favorites' => $favorites,
            'pending' => $this->getAllPendingJobs(),
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
        $overHourOld = $this->clock
            ->read()
            ->modify('-60 minutes');

        $buildCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Building'))
            ->andWhere(Criteria::expr()->lt('created', $overHourOld))
            ->orderBy(['created' => 'DESC']);

        $pushCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Pushing'))
            ->andWhere(Criteria::expr()->lt('created', $overHourOld))
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
     * @param User $user
     *
     * @return Application[]
     */
    private function findFavorites(User $user)
    {
        if (!$settings = $user->settings()) {
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
