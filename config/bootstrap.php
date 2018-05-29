<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\Bootstrap;

use Hal\Core\DI;
use Hal\UI\CachedContainer;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$root = __DIR__ . '/..';
require_once "${root}/vendor/autoload.php";

$dotenv = new Dotenv;

if (file_exists("${root}/config/.env.default")) {
    $dotenv->load("${root}/config/.env.default");
}

if (file_exists("${root}/config/.env")) {
    $dotenv->load("${root}/config/.env");
}

$file = "${root}/src/CachedContainer.php";
$class = CachedContainer::class;
$options = [
    'class' => $class,
    'file' => $file
];

return DI::getDI([$root . '/config'], $options);

# .07s - .11s - Using cached container
# .17s - .22s - Using containerbuilder (not cached)
# .25s - .33s - Building and caching (not cached)
