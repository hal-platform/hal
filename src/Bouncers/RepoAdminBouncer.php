<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Session;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Panthor\TemplateInterface;
use Slim\Route;
use Slim\Slim;

/**
 * A bouncer that checks to see if the current user is a super admin
 */
class RepoAdminBouncer
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @var TemplateInterface
     */
    private $twig;

    /**
     * @var LoginBouncer
     */
    private $loginBouncer;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var RepositoryRepository
     */
    private $repositories;

    /**
     * @var Slim
     */
    private $slim;

    /**
     * @param Session $session
     * @param PermissionsService $permissions
     * @param TemplateInterface $twig
     * @param LoginBouncer $loginBouncer
     */
    public function __construct(
        Session $session,
        PermissionsService $permissions,
        TemplateInterface $twig,
        LoginBouncer $loginBouncer,
        Route $route,
        RepositoryRepository $repositories,
        Slim $slim
    ) {
        $this->session = $session;
        $this->permissions = $permissions;
        $this->twig = $twig;
        $this->loginBouncer = $loginBouncer;
        $this->route = $route;
        $this->repositories = $repositories;
        $this->slim = $slim; // only for 404 calls
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws Stop
     *
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        // Let login bouncer run first
        call_user_func($this->loginBouncer, $request, $response);

        $user = $this->session->get('user');

        // ASSUMPTION: the repository id will always be named 'repository' in the route
        // dumb, but we need to look up the repo key here for user permission checks

        $repo = $this->repositories->findOneBy(['id' => $this->route->getParam('repository')]);

        // repo does not exist
        if (!$repo instanceof Repository) {
            $this->slim->notFound();
            throw new Stop;
        }

        if ($this->permissions->allowRepoAdmin($user, $repo->getKey())) {
            return;
        }

        $rendered = $this->twig->render([]);
        $response->setStatus(403);
        $response->setBody($rendered);

        throw new Stop;
    }
}
