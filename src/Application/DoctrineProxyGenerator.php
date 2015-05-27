<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Application;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class DoctrineProxyGenerator
{
    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->em = self::mockEntityManager($container);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return EntityManager
     */
    public static function mockEntityManager(ContainerInterface $container)
    {
        $fakeConn = [
            'driver' => 'pdo_sqlite',
            'memory' => true
        ];

        $em = EntityManager::create(
            $fakeConn,
            $container->get('doctrine.config'),
            $container->get('doctrine.em.events')
        );

        $container->get('doctrine.em.configurator')->configure($em);

        return $em;
    }

    /**
     * @return bool
     */
    public function __invoke()
    {
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        $proxy = $this->em->getProxyFactory();
        $proxyDir = $this->em->getConfiguration()->getProxyDir();

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
