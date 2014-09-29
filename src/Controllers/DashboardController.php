<?php

namespace QL\Hal\Controllers;

use Doctrine\Common\Collections\Criteria;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Layout;
use QL\Hal\Services\PermissionsService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class DashboardController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    /**
     *  @var LdapUser
     */
    private $user;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param LdapUser $user
     * @param PermissionsService $permissions
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param UserRepository $userRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        LdapUser $user,
        PermissionsService $permissions,
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        UserRepository $userRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->user = $user;
        $this->permissions = $permissions;

        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->userRepo = $userRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        // user that will show pushes for front end work findOneBy(['id' => 2024851])
        $user = $this->userRepo->findOneBy(['id' => $this->user->commonId()]);
        $recentBuilds = $this->buildRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);
        $recentPushes = $this->pushRepo->findBy(['user' => $user], ['created' => 'DESC'], 5);

        $pending = [];
        if ($this->permissions->allowAdmin($user)) {
            $pending = $this->getAllPendingJobs();
        }

        $rendered = $this->layout->render($this->template, [
            'user' => $this->user,
            'repositories' => $this->permissions->userRepositories($this->user),
            'pending' => $pending,
            'builds' => $recentBuilds,
            'pushes' => $recentPushes
        ]);

        $response->setBody($rendered);
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
