<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application;

use Doctrine\ORM\EntityManager;

class DoctrineProxyGenerator
{
    /**
     * @param EntityManager $em
     *
     * @return bool
     */
    public static function generateProxies(EntityManager $em)
    {
        $metas = $em->getMetadataFactory()->getAllMetadata();
        $proxy = $em->getProxyFactory();
        $proxyDir = $em->getConfiguration()->getProxyDir();

        if (count($metas) === 0) {
            echo "No entities to process.\n";
            return true;
        }

        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, 0777, true);
        }

        $proxyDir = realpath($proxyDir);

        if (!file_exists($proxyDir)) {
            echo sprintf('Proxies destination directory "%s" does not exist.', $proxyDir) . "\n";
            return false;
        }

        if (!is_writable($proxyDir)) {
            echo sprintf('Proxies destination directory "%s" does not have write permissions.', $proxyDir) . "\n";
            return false;
        }

        foreach ($metas as $metadata) {
            echo sprintf('Processing "%s"', $metadata->name) . "\n";
        }

        // Generate Proxies
        $proxy->generateProxyClasses($metas);

        echo "\n";
        echo sprintf('Proxy classes generated to "%s"', $proxyDir) . "\n";

        return true;
    }
}
