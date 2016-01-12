<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Middleware;

use Doctrine\ORM\EntityManager;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\AuditLog;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Automatically look up entities in the route parameters and fetch them from the DB.
 *
 * Throw a 404 if not found in the DB.
 */
class RequireEntityMiddleware implements MiddlewareInterface
{
    const KEY_TEMPLATE = 'kraken.%s';

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $map;

    /**
     * @param ContainerInterface $di
     * @param EntityManager $em
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        ContainerInterface $di,
        EntityManager $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->di = $di;
        $this->em = $em;

        $this->notFound = $notFound;
        $this->parameters = $parameters;

        // whitelist of route parameters and the entity they map to.
        $this->map = [
            'auditLog' => AuditLog::CLASS,
            'application' => Application::CLASS,
            'environment' => Environment::CLASS,
            'property' => Property::CLASS,
            'configuration' => Configuration::CLASS,
            'schema' => Schema::CLASS,
            'target' => Target::CLASS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        foreach ($this->parameters as $entity => $id) {

            if (!isset($this->map[$entity])) {
                continue;
            }

            if (!$this->lookup($entity, $id)) {
                return call_user_func($this->notFound);
            }
        }
    }

    /**
     * @param string $entityName
     * @param string $id
     *
     * @return bool
     */
    private function lookup($entityName, $id)
    {
        $fq = $this->map[$entityName];
        $key = sprintf(self::KEY_TEMPLATE, $entityName);

        $repository = $this->em->getRepository($fq);
        if (!$entity = $repository->find($id)) {
            return false;
        }

        $this->di->set($key, $entity);
        return true;
    }
}
