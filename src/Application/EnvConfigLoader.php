<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Container Modifier for use when loading the container, or dumping the compiled container.
 */
class EnvConfigLoader
{
    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    public function load(ContainerInterface $container)
    {
        // Save database password from encrypted properties on Hal deployment
        if (false !== ($property = getenv('ENCRYPTED_HAL_DB_PASS'))) {
            $container->setParameter('database.password', $property);
        }

        // Save commit SHA on Hal deployment
        if (false !== ($property = getenv('HAL_COMMIT'))) {
            $container->setParameter('application.sha', $property);
        }

        // This session secret is generated on each deploy, so that:
        // 1. Users are automatically logged out when new HAL code is deployed
        // 2. Users cannot look in code and find a way to decrypt and thus fake session data
        if (false !== ($property = getenv('HAL_ENCRYPTION_SECRET'))) {
            $container->setParameter('session.encryption.secret', $property);
        }
    }
}
