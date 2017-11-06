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
use Hal\Core\Entity\{
    Application,
    // AuditEvent,
    Build,
    Credential,
    EncryptedProperty,
    Environment,
    // Group,
    JobEvent,
    // JobMeta,
    // JobProcess,
    Organization,
    Release,
    // SystemSetting,
    Target,
    User,
    UserPermission,
    // UserSettings,
    UserToken
};
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
        'release' => Release::class,
        'event' => JobEvent::class,

        'user' => User::class,
        'user_permission' => UserPermission::class,
        'token' => UserToken::class,

        'organization' => Organization::class,
        'application' => Application::class,

        'target' => Target::class,
        'encrypted' => EncryptedProperty::class,

        'environment' => Environment::class,
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
