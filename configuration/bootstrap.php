<?php
/**
 * @copyright (c) 2014 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\Bootstrap;

use Hal\UI\CachedContainer;
use QL\Panthor\Bootstrap\Di;

$root = __DIR__ . '/..';
require_once $root . '/vendor/autoload.php';

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

$container = Di::getDi($root, CachedContainer::class);

// Custom application logic here

return $container;
