<?php
# src/QL/Hal/Helpers/UrlHelper.php

namespace QL\Hal\Helpers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;
use QL\Hal\Services\GithubService;

/**
 *  Url Helper
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class UrlHelper
{
    /**
     *  @var Request
     */
    private $request;

    /**
     *  @var Response
     */
    private $response;

    /**
     *  @var Router
     */
    private $router;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @param Request $request
     * @param Response $response
     * @param Router $router
     * @param GithubService $github
     */
    public function __construct(
        Request $request,
        Response $response,
        Router $router,
        GithubService $github
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->router = $router;
        $this->github = $github;
    }

    /**
     *  Get the full URL for a given route name
     *
     *  @param string $route
     *  @param array $params
     *  @return string
     */
    public function urlFor($route, array $params = [])
    {
        return $this->request->getUrl() . $this->router->urlFor($route, $params);
    }

    /**
     *  Generate a redirect response for a given route name
     *
     *  @param string $route
     *  @param array $params
     *  @param int $code
     */
    public function redirectFor($route, array $params = [], $code = 302)
    {
        $this->response->redirect(
            $this->urlFor($route, $params),
            $code
        );
    }

    /**
     *  Get the URL for a Github user
     *
     *  @param $user
     *  @return string
     */
    public function githubUserUrl($user)
    {
        return sprintf(
            'http://git/%s',
            $user
        );
    }

    /**
     *  Get the URL for a Github repository
     *
     *  @param string $user
     *  @param string $repo
     *  @return string
     */
    public function githubRepoUrl($user, $repo)
    {
        return sprintf(
            '%s/%s',
            $this->githubUserUrl($user),
            $repo
        );
    }

    // http://git/web-core/hal/tree/2.0.1
    // http://git/web-core/hal/pull/77
    // http://git/web-core/hal/commit/c938c1804a6e6950abb33461b9a2dd98ff08bfcf
    // http://git/web-core/hal/tree/maintenance

    /**
     *  Get the URL for a Github repository commit
     *
     *  @param string $user
     *  @param string $repo
     *  @param string $commit
     *  @return string
     */
    public function githubCommitUrl($user, $repo, $commit)
    {
        return sprintf(
            '%s/commit/%s',
            $this->githubRepoUrl($user, $repo),
            $commit
        );
    }

    /**
     *  Get the URL of a Github repository treeish
     *
     *  @param $user
     *  @param $repo
     *  @param $treeish
     *  @return string
     */
    public function githubTreeUrl($user, $repo, $treeish)
    {
        return sprintf(
            '%s/tree/%s',
            $this->githubRepoUrl($user, $repo),
            $treeish
        );
    }

    /**
     *  Get the URL of a Github pull request
     *
     *  @param $user
     *  @param $repo
     *  @param $number
     *  @return string
     */
    public function githubPullRequestUrl($user, $repo, $number)
    {
        return sprintf(
            '%s/pull/%s',
            $this->githubRepoUrl($user, $repo),
            $number
        );
    }

    /**
     * Get the URL for an arbitrary Github reference
     *
     * @param $user
     * @param $repo
     * @param $reference
     * @return string
     */
    public function githubReferenceUrl($user, $repo, $reference)
    {
        if ($tag = $this->github->parseRefAsTag($reference)) {
            return $this->githubTreeUrl($user, $repo, $tag);
        }

        if ($pull = $this->github->parseRefAsPull($reference)) {
            return $this->githubPullRequestUrl($user, $repo, $pull);
        }

        if ($commit = $this->github->parseRefAsCommit($reference)) {
            return $this->githubCommitUrl($user, $repo, $commit);
        }

        // default to branch
        return $this->githubTreeUrl($user, $repo, $reference);
    }
}
