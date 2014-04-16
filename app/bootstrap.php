<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

const CONFIG_FILE       = 'app/config.yml';
const ROUTES_FILE       = 'app/routes.yml';

$root = __DIR__.'/../';
require_once $root.'/vendor/autoload.php';

/**
 *  Check if a file exists and is readable the path
 *
 *  @param string $path
 *  @return string
 *  @throws Exception
 */
function check_file($path)
{
    if (is_readable($path)) {
        return $path;
    } else {
        throw new Exception("File $path is not readable");
    }
}

$locator    = new FileLocator($root);

// Check Required Files
check_file($locator->locate(CONFIG_FILE));
check_file($locator->locate(ROUTES_FILE));

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

// DI Container
$container = new ContainerBuilder;
$builder = new YamlFileLoader($container, $locator);
$builder->load(CONFIG_FILE);
$container->setParameter('root', $root);
$container->set('dic', $container);
$container->compile();
