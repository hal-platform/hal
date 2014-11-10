<?php

namespace QL\Hal\Helpers;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lazy User Helper
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class LazyUserHelper
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->container->get('currentUser.ldap');
    }
}
