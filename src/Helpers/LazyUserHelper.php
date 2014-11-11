<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use QL\Hal\Core\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyUserHelper
{
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
        return $this->container->get('currentUser', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }
}
