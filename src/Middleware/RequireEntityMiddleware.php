<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\{
    Application,
    Credential,
    EncryptedProperty,
    Environment,
    Job,
    Organization,
    Target,
    TargetTemplate,
    User
};
// use Hal\Core\Entity\Job\JobArtifact;
use Hal\Core\Entity\Job\JobEvent;
// use Hal\Core\Entity\Job\JobMeta;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Entity\User\UserToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\GUID;
use QL\Panthor\MiddlewareInterface;

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
    public const KNOWN_ENTITIES = [
        'application' => Application::class,
        'credential' => Credential::class,
        'encrypted' => EncryptedProperty::class,
        'environment' => Environment::class,
        'organization' => Organization::class,
        'target' => Target::class,
        'template' => TargetTemplate::class,

        'job' => Job::class,
        'build' => Build::class,
        'release' => Release::class,
        'event' => JobEvent::class,

        'user' => User::class,
        'user_permission' => UserPermission::class,
        'user_token' => UserToken::class,

        'system_idp' => UserIdentityProvider::class,
        'system_vcs' => VersionControlProvider::class
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

        $isGUID = GUID::createFromHex($id);
        if (!$isGUID) {
            return false;
        }

        $repository = $this->em->getRepository($fq);
        if (!$entity = $repository->find($id)) {
            return false;
        }

        return $entity;
    }
}
