<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bootstrap;

use QL\Hal\Di;

$root = __DIR__ . '/..';
require_once $root . '/vendor/autoload.php';

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

$container = Di::getDi($root, 'QL\Hal\CachedContainer', function ($container) {
    // Overwrite sha param if env var is found. This will always be set for every script during the build process.
    if (array_key_exists('HAL_COMMIT', $_SERVER)) {
        $container->setParameter('application.sha', $_SERVER['HAL_COMMIT']);
    }

    // Overwrite the encryption secret if an env var is found.
    if (array_key_exists('HAL_ENCRYPTION_SECRET', $_SERVER)) {
        $container->setParameter('session.encryption.secret', $_SERVER['HAL_ENCRYPTION_SECRET']);
    }
});

// Custom application logic here

return $container;
