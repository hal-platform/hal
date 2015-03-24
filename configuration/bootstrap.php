<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bootstrap;

use QL\Panthor\Bootstrap\Di;

$root = __DIR__ . '/..';
require_once $root . '/vendor/autoload.php';

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

$container = Di::getDi($root, 'QL\Hal\CachedContainer');

// Custom application logic here

return $container;
