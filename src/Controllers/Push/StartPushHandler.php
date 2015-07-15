<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\StickyEnvironmentService;
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
     * @type EntityRepository
     */
    private $buildRepo;
    private $pushRepo;
    private $deployRepo;

    /**
     * @type EntityManagerInterface
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
     * @type PermissionService
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
     * @param EntityManagerInterface $em
     * @param Url $url
     * @param User $currentUser
     * @param PermissionService $permissions
     * @param JobIdGenerator $unique
     * @param Request $request
     * @param Context $context
     * @param StickyEnvironmentService $stickyService
     * @param array $parameters
     */
    public function __construct(
        Session $session,
        EntityManagerInterface $em,
        Url $url,
        User $currentUser,
        PermissionService $permissions,
        JobIdGenerator $unique,
        Request $request,
        Context $context,
        StickyEnvironmentService $stickyService,
        array $parameters
    ) {
        $this->session = $session;

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->deployRepo = $em->getRepository(Deployment::CLASS);
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

        $environment = $build->environment();
        $application = $build->application();
        $pushes = [];

        $canUserPush = $this->permissions->canUserPush($this->currentUser, $application, $environment);

        $criteria = (new Criteria)->where(Criteria::expr()->in('id', $deploymentIds));
        $deployments = $this->deployRepo->matching($criteria);
        $deployments = $deployments->toArray();

        $kvd = [];
        foreach ($deployments as $deployment) {
            $kvd[$deployment->id()] = $deployment;
        }

        foreach ($deploymentIds as $deploymentId) {
            if (!isset($kvd[$deploymentId])) {
                return $this->context->addContext(['errors' => [self::ERR_BAD_DEP]]);
            }

            $deployment = $kvd[$deploymentId];
            $server = $deployment->server();

            if ($environment !== $server->environment()) {
                return $this->context->addContext([
                    'errors' => [sprintf(self::ERR_WRONG_ENV, $environment->name())]
                ]);
            }

            if (!$canUserPush) {
                return $this->context->addContext([
                    'errors' => [sprintf(self::ERR_NO_PERM, $server->name())]
                ]);
            }

            $id = $this->unique->generatePushId();

            $push = (new Push)
                ->withId($id)
                ->withStatus('Waiting')
                ->withUser($this->currentUser)
                ->withBuild($build)
                ->withDeployment($deployment)
                ->withApplication($application);

            $pushes[] = $push;
        }

        $this->dupeCatcher($pushes);

        // commit pushes
        foreach ($pushes as $push) {
            $this->em->persist($push);
        }

        $this->em->flush();

        // override sticky environment
        $this->stickyService->save($application->id(), $deployment->server()->environment()->id());

        $this->session->flash(self::NOTICE_DONE, 'success');
        $this->url->redirectFor('application.status', ['application' => $application->id()]);
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
            return $push->id();
        }, $pushes);

        $dupes = $this->pushRepo->findBy(['id' => $ids]);
        if ($dupes) {
            $dupeIds = array_map(function($push) {
                return $push->id();
            }, $dupes);

            $dupePushes = array_filter($pushes, function($push) use ($dupeIds) {
                return in_array($push->id(), $dupeIds);
            });

            foreach ($dupePushes as $push) {
                $push->withId($this->unique->generatePushId());
            }

            $this->dupeCatcher($pushes);
        }
    }
}
