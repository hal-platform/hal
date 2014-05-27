<?php

namespace QL\Hal\Controllers\Repository\Build;

use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Build Start Handle Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class BuildStartHandleController
{
    const VAL_PULL = '#^pull/[0-9]+$#';

    const VAL_COMMIT = '#^none$#';

    const VAL_TAG = '#^tag/(?!/|.*([/.]\.|//|@\{|\\\\))[^\040\177 ~^:?*\[]+(?<!\.lock|[/.])$#';

    const VAL_BRANCH = '#^(?!/|.*([/.]\.|//|@\{|\\\\))[^\040\177 ~^:?*\[]+(?<!\.lock|[/.])$#';

    const VAL_SHA = '#^[0-9a-f]{40}$#';

    const VAL_ENV = '#^[A-Za-z0-9]+$#';

    private $session;

    private $repoRepo;

    private $userRepo;

    private $envRepo;

    private $url;

    private $user;

    public function __construct(
        Session $session,
        RepositoryRepository $repoRepo,
        UserRepository $userRepository,
        EnvironmentRepository $envRepo,
        UrlHelper $url,
        User $user
    ) {
        $this->session = $session;
        $this->repoRepo = $repoRepo;
        $this->userRepo = $userRepository;
        $this->envRepo = $envRepo;
        $this->url = $url;
        $this->user = $user;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->findOneBy(['key' => $params['repo']]);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        $ref = $request->post('commitish', null);
        $sha = $request->post('hash', null);

        $env = $this->envRepo->findOneBy(['key' => $request->post('environment', null)]);

        if (!$env) {
            $this->session->addFlash('You must select an environment to build for.');
            $response->redirect($this->url->urlFor('build.start', ['repo' => $repo->getKey()]), 303);
            return;
        }

        if (preg_match(self::VAL_TAG, $ref)) {
            // tag

        } elseif (preg_match(self::VAL_PULL, $ref)) {
            // pull request

        } elseif (preg_match(self::VAL_COMMIT, $ref)) {
            // commit

        } elseif (preg_match(self::VAL_BRANCH, $ref)) {
            // branch

        } else {
            $this->session->addFlash('You must select a valid git ref.');
            $response->redirect($this->url->urlFor('build.start', ['repo' => $repo->getKey()]), 303);
            return;
        }

//        var_dump($ref, $sha, $env);
//
//        var_dump([
//            'push' => preg_match(self::VAL_PULL, $ref),
//            'tag' => preg_match(self::VAL_TAG, $ref),
//            'branch' => preg_match(self::VAL_BRANCH, $ref),
//            'commit' => preg_match(self::VAL_COMMIT, $ref),
//            'sha' => preg_match(self::VAL_SHA, $sha),
//            'env' => preg_match(self::VAL_ENV, $env)
//        ]);
//
//        die();


        $response->redirect($this->url->urlFor('repo', ['repo' => $repo->getKey()]), 303);
    }
}
