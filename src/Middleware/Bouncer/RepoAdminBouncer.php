<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\Bouncer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use Slim\Exception\Stop;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks to see if the current user is a super admin
 */
class RepoAdminBouncer implements MiddlewareInterface
{
    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type LoginBouncer
     */
    private $loginBouncer;

    /**
     * @type Route
     */
    private $route;

    /**
     * @type EntityRepository
     */
    private $repoRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ContainerInterface $di
     * @param PermissionsService $permissions
     * @param TemplateInterface $template
     * @param LoginBouncer $loginBouncer
     * @param EntityManagerInterface $em
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        ContainerInterface $di,
        PermissionsService $permissions,
        TemplateInterface $template,
        LoginBouncer $loginBouncer,
        EntityManagerInterface $em,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->di = $di;
        $this->permissions = $permissions;
        $this->template = $template;
        $this->loginBouncer = $loginBouncer;
        $this->repoRepo = $em->getRepository(Repository::CLASS);

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws Stop
     */
    public function __invoke()
    {
        // Let login bouncer run first
        call_user_func($this->loginBouncer);

        $user = $this->di->get('currentUser');

        // ASSUMPTION: the repository id will always be named 'repository' in the route
        // dumb, but we need to look up the repo key here for user permission checks
        $repositoryId = isset($this->parameters['repository']) ? $this->parameters['repository'] : null;

        // repo does not exist
        if (!$repo = $this->repoRepo->find($repositoryId)) {
            return call_user_func($this->notFound);
        }

        if ($this->permissions->allowRepoAdmin($user, $repo->getKey())) {
            return;
        }

        $this->template->render();
        $this->response->setStatus(403);

        throw new Stop;
    }
}
