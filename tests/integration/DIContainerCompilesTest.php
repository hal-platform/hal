<?php

namespace Hal\UI;

use Hal\UI\CachedContainer;
use PHPUnit\Framework\TestCase;
use QL\Panthor\Bootstrap\DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;

class ContainerIntegrationTest extends TestCase
{
    private $rootPath;
    private $envFile;

    public function setUp()
    {
        $this->rootPath = realpath(__DIR__ . '/../..');
        $this->envFile = "{$this->rootPath}/config/.env.dev.dist";

        putenv("PANTHOR_APPROOT={$this->rootPath}");
        putenv("HAL_DB_USER=postgres");
        putenv("HAL_DB_PASSWORD=");
    }

    public function tearDown()
    {
        putenv("PANTHOR_APPROOT=");
        putenv("HAL_DB_USER=postgres");
        putenv("HAL_DB_PASSWORD=");
    }

    /**
     * Tests to make sure a parse exception isn't thrown on our yaml configs
     */
    public function testContainerCompiles()
    {
        $dotenv = new Dotenv;
        $dotenv->load($this->envFile);

        $options = [
            'class' => CachedContainer::class,
            'file' => "{$this->rootPath}/src/CachedContainer.php"
        ];

        $container = DI::getDI($this->rootPath, $options);

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }
}
