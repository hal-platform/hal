<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Utility;

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
    public function __invoke()
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
