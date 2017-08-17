<?php
/**
 * @copyright (c) 2014 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\Bootstrap;

use Hal\UI\Application\DI2;
use Hal\UI\CachedContainer;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$root = realpath(__DIR__ . '/../');
require_once $root . '/vendor/autoload.php';

$dotenv = new Dotenv;
$dotenv->load(__DIR__ . '/.env');

$file = "${root}/src/CachedContainer.php";
$class = CachedContainer::class;
$options = [
    'class' => $class,
    'file' => $file
];

return DI2::getDI($root, $options);

# .07s - .11s - Using cached container
# .17s - .22s - Using containerbuilder (not cached)
# .25s - .33s - Building and caching (not cached)
