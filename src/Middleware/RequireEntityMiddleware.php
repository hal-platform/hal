<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use QL\Panthor\HTTPProblem\ProblemRenderingTrait;
use QL\Panthor\MiddlewareInterface;
use Slim\Route;

/**
 * Automatically look up entities in the route parameters and fetch them from the DB.
 *
 * Throw a 404 if not found in the DB.
 */
class RequireEntityMiddleware implements MiddlewareInterface
{
    use ProblemRenderingTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @var bool
     */
    private $isAPI;

    /**
     * @var array
     */
    private $map;

    /**
     * @param EntityManagerInterface $em
     * @param ProblemRendererInterface $problemRenderer
     * @param callable $notFound
     * @param bool $isAPI
     */
    public function __construct(
        EntityManagerInterface $em,
        ProblemRendererInterface $problemRenderer,
        callable $notFound,
        $isAPI = false
    ) {
        $this->em = $em;

        $this->problemRenderer = $problemRenderer;
        $this->notFound = $notFound;
        $this->isAPI = $isAPI;

        // whitelist of route parameters and the entity they map to.
        $this->map = [
            'build' => Build::CLASS,
            'push' => Push::CLASS,

            'user' => User::CLASS,
            'userPermission' => UserPermission::CLASS,
            'userType' => UserType::CLASS,

            'application' => Application::CLASS,
            'credential' => Credential::CLASS,
            'deployment' => Deployment::CLASS,
            'encrypted' => EncryptedProperty::CLASS,
            'environment' => Environment::CLASS,
            'server' => Server::CLASS,

            'pool' => DeploymentPool::CLASS,
            'view' => DeploymentView::CLASS,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');

        $params = $route->getArguments();

        foreach ($params as $entity => $id) {

            if (!isset($this->map[$entity])) {
                continue;
            }

            if (!$entityObj = $this->lookup($entity, $id)) {

                if ($this->isAPI) {
                    return $response = $this->renderProblem(
                        $response,
                        $this->problemRenderer,
                        new HTTPProblem(404, sprintf('%s not found', ucfirst($entity)))
                    );
                } else {
                    return call_user_func($this->notFound, $request, $response);
                }
            }

            $request = $request->withAttribute($this->map[$entity], $entityObj);
        }

        return $next($request, $response);
    }

    /**
     * @param string $entityName
     * @param string $id
     *
     * @return mixed
     */
    private function lookup($entityName, $id)
    {
        $fq = $this->map[$entityName];

        $repository = $this->em->getRepository($fq);
        if (!$entity = $repository->find($id)) {
            return false;
        }

        return $entity;
    }
}
