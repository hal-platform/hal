<?php

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Build Push Handle Controller
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class BuildPushHandleController
{
    const ERR_NO_DEPS = 'You must select at least one deployment.';
    const ERR_BAD_DEP = 'One or more of the selected deployments is invalid.';
    const ERR_NO_PERM = "You attempted to push to %s but don't have permission.";
    const NOTICE_DONE = "The build has been queued to be pushed to the requested servers.";

    /**
     * @var Session
     */
    private $session;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var DeploymentRepository
     */
    private $deployRepo;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var User
     */
    private $user;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @param Session $session
     * @param BuildRepository $buildRepo
     * @param DeploymentRepository $deployRepo
     * @param UserRepository $userRepo
     * @param EntityManager $em
     * @param UrlHelper $url
     * @param User $user
     * @param PermissionsService $permissions
     */
    public function __construct(
        Session $session,
        BuildRepository $buildRepo,
        DeploymentRepository $deployRepo,
        UserRepository $userRepo,
        EntityManager $em,
        UrlHelper $url,
        User $user,
        PermissionsService $permissions
    ) {
        $this->session = $session;
        $this->buildRepo = $buildRepo;
        $this->deployRepo = $deployRepo;
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->url = $url;
        $this->user = $user;
        $this->permissions = $permissions;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['build']]);

        if (!$build || $build->getStatus() != 'Success') {
            call_user_func($notFound);
            return;
        }

        $deploymentIds = $request->post('deployments', []);

        if (!is_array($deploymentIds) || count($deploymentIds) == 0) {
            $this->session->addFlash(self::ERR_NO_DEPS);
            $response->redirect($request->getReferer(), 303);
            return;
        }

        $pushes = [];

        foreach ($deploymentIds as $deploymentId) {
            $deployment = $this->deployRepo->findOneBy(['id' => $deploymentId]);

            if (!$deployment) {
                $this->session->addFlash(self::ERR_BAD_DEP);
                $response->redirect($request->getReferer(), 303);
                return;
            }

            if (!$this->permissions->allowPush($this->user, $build->getRepository()->getKey(), $deployment->getServer()->getEnvironment()->getKey())) {
                $this->session->addFlash(
                    sprintf(
                        self::ERR_NO_PERM,
                        $deployment->getServer()->getName()
                    )
                );
                $response->redirect($request->getReferer(), 303);
                return;
            }

            $push = new Push();
            $push->setStatus('Waiting');
            $push->setUser($this->userRepo->findOneBy(['id' => $this->user->commonId()]));
            $push->setBuild($build);
            $push->setDeployment($deployment);
            $pushes[] = $push;
        }

        // commit pushes
        foreach ($pushes as $push) {
            $this->em->persist($push);
        }

        $this->session->addFlash(self::NOTICE_DONE);
        $response->redirect($this->url->urlFor('repository.status', ['id' => $build->getRepository()->getId()]), 303);
    }
}