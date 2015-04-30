<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Middleware;

use Doctrine\ORM\EntityManager;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Target;
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
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @type array
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
            'application' => Application::CLASS,
            'environment' => Environment::CLASS,
            'property' => Property::CLASS,
            'configuration' => Configuration::CLASS,
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
