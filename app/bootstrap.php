<?php

namespace QL\Hal;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;

const CONFIG_FILE       = 'app/config.yml';
const DI_CONFIG_FILE    = 'app/di.yml';
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
check_file($locator->locate(DI_CONFIG_FILE));
check_file($locator->locate(ROUTES_FILE));

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

// Import Config
$yml        = file_get_contents($locator->locate(CONFIG_FILE));
$config     = new ParameterBag(Yaml::parse($yml));

// DI Container
$container = new ContainerBuilder($config);
$builder = new YamlFileLoader($container, $locator);
$builder->load(DI_CONFIG_FILE);
$container->setParameter('root', $root);
$container->set('dic', $container);
$container->compile();
