<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;

class ServerNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;
    public $envNormalizer;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $this->envNormalizer = Mockery::mock('QL\Hal\Api\EnvironmentNormalizer');
    }

    public function testNormalizationOfLinkedResource()
    {
        $server = new Server;
        $server->setId('1234');

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');

        $normalizer = new ServerNormalizer($this->api, $this->url, $this->envNormalizer);
        $actual = $normalizer->normalizeLinked($server);

        $expected = [
            'id' => '1234',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationWithoutCriteria()
    {
        $environment = new Environment;
        $environment->setId('5678');

        $server = new Server;
        $server->setId('1234');
        $server->setName('testserver');
        $server->setEnvironment($environment);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');
        $this->envNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-env');

        $normalizer = new ServerNormalizer($this->api, $this->url, $this->envNormalizer);
        $actual = $normalizer->normalize($server);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'name' => 'testserver',
            'environment' => 'normalized-env',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationCriteriaCascadesToChildEntity()
    {
        $environment = new Environment;
        $environment->setId('5678');

        $server = new Server;
        $server->setId('1234');
        $server->setName('testserver');
        $server->setEnvironment($environment);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');
        $this->envNormalizer
            ->shouldReceive('normalize')
            ->with($environment, ['derp'])
            ->andReturn('normalized-env');

        $normalizer = new ServerNormalizer($this->api, $this->url, $this->envNormalizer);
        $actual = $normalizer->normalize($server, ['environment' => ['derp']]);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'name' => 'testserver',
            'environment' => 'normalized-env',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }
}
