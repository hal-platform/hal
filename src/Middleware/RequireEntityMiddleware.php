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
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
use QL\Panthor\MiddlewareInterface;
use Slim\Route;

/**
 * Automatically look up entities in the route parameters and fetch them from the DB.
 *
 * Throw a 404 if not found in the DB.
 */
class RequireEntityMiddleware implements MiddlewareInterface
{
    /**
     * Map of known entities from their route parameter name to FQCN.
     */
    const KNOWN_ENTITIES = [
        'build' => Build::class,
        'push' => Push::class,
        'event' => EventLog::class,

        'user' => User::class,
        'user_permission' => UserPermission::class,
        'user_type' => UserType::class,
        'token' => Token::class,

        'organization' => Group::class,
        'application' => Application::class,

        'target' => Deployment::class,
        'encrypted' => EncryptedProperty::class,

        'environment' => Environment::class,
        'server' => Server::class,
        'credential' => Credential::class,
    ];

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @param EntityManagerInterface $em
     * @param callable $notFound
     */
    public function __construct(EntityManagerInterface $em, callable $notFound)
    {
        $this->em = $em;
        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $params = $request
            ->getAttribute('route')
            ->getArguments();

        foreach ($params as $param => $id) {

            if (!isset(self::KNOWN_ENTITIES[$param])) {
                continue;
            }

            if (!$entity = $this->lookup($param, $id)) {
                return ($this->notFound)($request, $response);
            }

            $request = $request->withAttribute(self::KNOWN_ENTITIES[$param], $entity);
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
        $fq = self::KNOWN_ENTITIES[$entityName];

        $repository = $this->em->getRepository($fq);
        if (!$entity = $repository->find($id)) {
            return false;
        }

        return $entity;
    }
}
