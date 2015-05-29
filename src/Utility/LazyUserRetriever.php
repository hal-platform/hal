<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Utility;

use QL\Hal\Core\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;

/**
 * Attempt to get the currently logged in user. The user is a synthetic service, so its a bit tricky to avoid
 * blowing up.
 */
class LazyUserRetriever
{
    const SERVICE_KEY = 'currentUser';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        if ($this->container instanceof IntrospectableContainerInterface && !$this->container->initialized(self::SERVICE_KEY)) {
            return;
        }

        if (!$this->container->has(self::SERVICE_KEY)) {
            return;
        }

        return $this->container->get(self::SERVICE_KEY, ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }
}
