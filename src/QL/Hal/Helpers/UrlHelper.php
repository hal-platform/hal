<?php
# src/QL/Hal/Helpers/UrlHelper.php

namespace QL\Hal\Helpers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

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
     *  @param Request $request
     *  @param Response $response
     *  @param Router $router
     */
    public function __construct(Request $request, Response $response, Router $router)
    {
        $this->request = $request;
        $this->response = $response;
        $this->router = $router;
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
        return $this->request->getRootUri() . $this->router->urlFor($route, $params);
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
}
