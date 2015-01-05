<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class PushStartHandler
{
    const ERR_NO_DEPS = 'You must select at least one deployment.';
    const ERR_BAD_DEP = 'One or more of the selected deployments is invalid.';
    const ERR_WRONG_ENV = 'This build can only be pushed to deployments in the %s environment.';
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
     * @var PushRepository
     */
    private $pushRepo;

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
    private $currentUser;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @var JobIdGenerator
     */
    private $unique;

    /**
     * @param Session $session
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param DeploymentRepository $deployRepo
     * @param UserRepository $userRepo
     * @param EntityManager $em
     * @param UrlHelper $url
     * @param User $currentUser
     * @param PermissionsService $permissions
     * @param JobIdGenerator $unique
     */
    public function __construct(
        Session $session,
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        DeploymentRepository $deployRepo,
        UserRepository $userRepo,
        EntityManager $em,
        UrlHelper $url,
        User $currentUser,
        PermissionsService $permissions,
        JobIdGenerator $unique
    ) {
        $this->session = $session;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->deployRepo = $deployRepo;
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->url = $url;
        $this->currentUser = $currentUser;
        $this->permissions = $permissions;
        $this->unique = $unique;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['build'], 'status' => 'Success']);

        if (!$build) {
            return call_user_func($notFound);
        }

        $deploymentIds = $request->post('deployments', []);

        if (!is_array($deploymentIds) || count($deploymentIds) == 0) {
            return $this->bailout(self::ERR_NO_DEPS, $build->getId());
        }

        $buildEnv = $build->getEnvironment();
        $repo = $build->getRepository();
        $pushes = [];

        foreach ($deploymentIds as $deploymentId) {
            $deployment = $this->deployRepo->findOneBy(['id' => $deploymentId]);

            if (!$deployment) {
                return $this->bailout(self::ERR_BAD_DEP, $build->getId());
            }

            $server = $deployment->getServer();

            if ($buildEnv->getId() !== $server->getEnvironment()->getId()) {
                $msg = sprintf(self::ERR_WRONG_ENV, $buildEnv->getKey());
                return $this->bailout($msg, $build->getId());
            }

            if (!$this->permissions->allowPush($this->currentUser, $repo->getKey(), $buildEnv->getKey())) {
                $msg = sprintf(self::ERR_NO_PERM, $server->getName());
                return $this->bailout($msg, $build->getId());
            }

            $push = new Push;

            $id = $this->unique->generatePushId();
            $user = $this->userRepo->find($this->currentUser->getId());

            $push->setId($id);
            $push->setStatus('Waiting');
            $push->setUser($user);
            $push->setBuild($build);
            $push->setDeployment($deployment);
            $push->setRepository($repo);

            $pushes[] = $push;
        }

        $this->dupeCatcher($pushes);

        // commit pushes
        foreach ($pushes as $push) {
            $this->em->persist($push);
        }

        $this->em->flush();

        $this->session->flash(self::NOTICE_DONE, 'success');
        $this->url->redirectFor('repository.status', ['id' => $repo->getId()]);
    }

    /**
     * @param string $message
     * @param string $buildId
     * @return null
     */
    private function bailout($message, $buildId)
    {
        $this->session->flash($message, 'error');
        $this->url->redirectFor('push.start', ['build' => $buildId], [], 303);
    }

    /**
     * This will find duplicate Push Ids (recursively) and change the id of the pushes to a new unique hash until
     * there are no duplicates.
     *
     * @param Push[] $pushes
     * @return null
     */
    private function dupeCatcher(array $pushes)
    {
        $ids = array_map(function($push) {
            return $push->getId();
        }, $pushes);

        $dupes = $this->pushRepo->findBy(['id' => $ids]);
        if ($dupes) {
            $dupeIds = array_map(function($push) {
                return $push->getId();
            }, $dupes);

            $dupePushes = array_filter($pushes, function($push) use ($dupeIds) {
                return in_array($push->getId(), $dupeIds);
            });

            foreach ($dupePushes as $push) {
                $push->setId($this->unique->generatePushId());
            }

            $this->dupeCatcher($pushes);
        }
    }
}
