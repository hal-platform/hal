<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Services\StickyEnvironmentService;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class StartPushHandler implements MiddlewareInterface
{
    const ERR_NO_DEPS = 'You must select at least one deployment.';
    const ERR_BAD_DEP = 'One or more of the selected deployments is invalid.';
    const ERR_WRONG_ENV = 'This build can only be pushed to deployments in the %s environment.';
    const ERR_NO_PERM = "You attempted to push to %s but don't have permission.";
    const NOTICE_DONE = "The build has been queued to be pushed to the requested servers.";

    /**
     * @type Session
     */
    private $session;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type DeploymentRepository
     */
    private $deployRepo;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type JobIdGenerator
     */
    private $unique;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Context
     */
    private $context;

    /**
     * @type StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param Session $session
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param DeploymentRepository $deployRepo
     * @param UserRepository $userRepo
     * @param EntityManager $em
     * @param Url $url
     * @param User $currentUser
     * @param PermissionsService $permissions
     * @param JobIdGenerator $unique
     * @param Request $request
     * @param Context $context
     * @param StickyEnvironmentService $stickyService
     * @param array $parameters
     */
    public function __construct(
        Session $session,
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        DeploymentRepository $deployRepo,
        UserRepository $userRepo,
        EntityManager $em,
        Url $url,
        User $currentUser,
        PermissionsService $permissions,
        JobIdGenerator $unique,
        Request $request,
        Context $context,
        StickyEnvironmentService $stickyService,
        array $parameters
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

        $this->request = $request;
        $this->context = $context;
        $this->stickyService = $stickyService;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $build = $this->buildRepo->findOneBy(['id' => $this->parameters['build'], 'status' => 'Success']);
        if (!$build) {
            // fall through to controller
            return;
        }

        $deploymentIds = $this->request->post('deployments', []);

        if (!is_array($deploymentIds) || count($deploymentIds) == 0) {
            return $this->context->addContext(['errors' => [self::ERR_NO_DEPS]]);
        }

        $buildEnv = $build->getEnvironment();
        $repo = $build->getRepository();
        $pushes = [];

        foreach ($deploymentIds as $deploymentId) {
            $deployment = $this->deployRepo->find($deploymentId);

            if (!$deployment) {
                return $this->context->addContext(['errors' => [self::ERR_BAD_DEP]]);
            }

            $server = $deployment->getServer();

            if ($buildEnv->getId() !== $server->getEnvironment()->getId()) {
                return $this->context->addContext([
                    'errors' => [sprintf(self::ERR_WRONG_ENV, $buildEnv->getKey())]
                ]);
            }

            if (!$this->permissions->allowPush($this->currentUser, $repo->getKey(), $buildEnv->getKey())) {
                return $this->context->addContext([
                    'errors' => [sprintf(self::ERR_NO_PERM, $server->getName())]
                ]);
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

        // override sticky environment
        $this->stickyService->save($repo->getId(), $deployment->getServer()->getEnvironment()->getId());

        $this->session->flash(self::NOTICE_DONE, 'success');
        $this->url->redirectFor('repository.status', ['id' => $repo->getId()]);
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
