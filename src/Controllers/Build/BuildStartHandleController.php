<?php

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Helpers\UniqueHelper;
use QL\Hal\Services\GithubService;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;


/**
 *  Build Start Handle Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class BuildStartHandleController
{
    /**
     *  Flash Messages
     */
    const ERR_NO_ENV = "You must select an environment to build for.";
    const ERR_NO_PERM = "You don't have permission to build for the %s environment.";
    const ERR_BAD_REF = "You must select a valid git reference.";
    const NOT_FINISH = 'Build has been queued for creation.';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var RepositoryRepository
     */
    private $repoRepo;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

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
     * @var GithubService
     */
    private $github;

    /**
     * @var UniqueHelper
     */
    private $unique;

    /**
     * @param Session $session
     * @param RepositoryRepository $repoRepo
     * @param UserRepository $userRepository
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $em
     * @param UrlHelper $url
     * @param User $user
     * @param PermissionsService $permissions
     * @param GithubService $github
     * @param UniqueHelper $unique
     */
    public function __construct(
        Session $session,
        RepositoryRepository $repoRepo,
        UserRepository $userRepository,
        EnvironmentRepository $envRepo,
        EntityManager $em,
        UrlHelper $url,
        User $user,
        PermissionsService $permissions,
        GithubService $github,
        UniqueHelper $unique
    ) {
        $this->session = $session;
        $this->repoRepo = $repoRepo;
        $this->userRepo = $userRepository;
        $this->envRepo = $envRepo;
        $this->em = $em;
        $this->url = $url;
        $this->user = $user;
        $this->permissions = $permissions;
        $this->github = $github;
        $this->unique = $unique;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {

        $repo = $this->repoRepo->findOneBy(['id' => $params['id']]);
        $env = $this->envRepo->findOneBy(['key' => $request->post('environment', null)]);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        if (!$env) {
            $this->session->addFlash(self::ERR_NO_ENV);
            $response->redirect($this->url->urlFor('build.start', ['id' => $repo->getId()]), 303);
            return;
        }

        if (!$this->permissions->allowBuild($this->user, $repo->getKey())) {
            $this->session->addFlash(sprintf(self::ERR_NO_PERM, $env->getKey()));
            $response->redirect($this->url->urlFor('build.start', ['id' => $repo->getId()]), 303);
            return;
        }

        $reference = ($request->post('reference', null) == 'commit')
            ? $request->post('commit', null)
            : $request->post('reference', null);

        if (!$result = $this->github->resolve($repo->getGithubUser(), $repo->getGithubRepo(), $reference)) {
            $this->session->addFlash(self::ERR_BAD_REF);
            $response->redirect($this->url->urlFor('build.start', ['id' => $repo->getId()]), 303);
            return;
        }

        list($reference, $commit) = $result;

        $build = new Build();
        $id = $this->unique->generateBuildId();

        $build->setId($id);
        $build->setStatus('Waiting');
        $build->setBranch($reference);
        $build->setCommit($commit);
        $build->setUser($this->userRepo->findOneBy(['id' => $this->user->commonId()]));
        $build->setRepository($repo);
        $build->setEnvironment($env);
        $this->em->persist($build);

        $this->session->addFlash(self::NOT_FINISH);
        $response->redirect($this->url->urlFor('build', ['build' => $id]), 303);
    }
}
