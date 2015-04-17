<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Middleware;

use Doctrine\ORM\EntityManager;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RequireEntityMiddleware implements MiddlewareInterface
{
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
     * @type string
     */
    private $entityName;

    /**
     * @param ContainerInterface $di
     * @param EntityManager $em
     * @param NotFound $notFound
     * @param array $parameters
     * @param string $entityName
     */
    public function __construct(
        ContainerInterface $di,
        EntityManager $em,
        NotFound $notFound,
        array $parameters,
        $entityName
    ) {
        $this->di = $di;
        $this->em = $em;

        $this->notFound = $notFound;
        $this->parameters = $parameters;
        $this->entityName = $entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $className = sprintf('QL\Kraken\Entity\%s', $this->entityName);
        $repository = $this->em->getRepository($className);

        if (!$entity = $repository->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $this->di->set('kraken.entity', $entity);
    }
}
