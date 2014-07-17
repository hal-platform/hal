<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Server;

class DeploymentNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;
    public $repoNormalizer;
    public $serverNormalizer;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $this->repoNormalizer = Mockery::mock('QL\Hal\Api\RepositoryNormalizer');
        $this->serverNormalizer = Mockery::mock('QL\Hal\Api\ServerNormalizer');
    }

    public function testNormalizationOfLinkedResource()
    {
        $deployment = new Deployment;
        $deployment->setId('1234');

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn([
                'self' => 'url',
                'lastPush' => 'url',
                'lastSuccessfulPush' => ['href' => 'url']
            ]);

        $normalizer = new DeploymentNormalizer($this->api, $this->url, $this->repoNormalizer, $this->serverNormalizer);
        $actual = $normalizer->normalizeLinked($deployment);

        $expected = [
            'id' => '1234',
            '_links' => [
                'self' => 'url',
                'lastPush' => 'url',
                'lastSuccessfulPush' => ['href' => 'url?status=Success']
            ]
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationWithoutCriteria()
    {
        $repo = new Repository;
        $server = new Server;

        $deployment = new Deployment;
        $deployment->setId('1234');
        $deployment->setPath('/server/path');
        $deployment->setRepository($repo);
        $deployment->setServer($server);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn([
                'self' => 'url',
                'lastPush' => 'url',
                'lastSuccessfulPush' => ['href' => 'url']
            ]);
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');

        $this->repoNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-repo');
        $this->serverNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-server');

        $normalizer = new DeploymentNormalizer($this->api, $this->url, $this->repoNormalizer, $this->serverNormalizer);
        $actual = $normalizer->normalize($deployment);

        $expected = [
            'id' => '1234',
            'path' => '/server/path',
            'repository' => 'normalized-repo',
            'server' => 'normalized-server',
            '_links' => [
                'self' => 'url',
                'lastPush' => 'url',
                'lastSuccessfulPush' => ['href' => 'url?status=Success']
            ]
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationCriteriaCascadesToChildEntity()
    {
        $repo = new Repository;
        $server = new Server;

        $deployment = new Deployment;
        $deployment->setId('1234');
        $deployment->setPath('/server/path');
        $deployment->setRepository($repo);
        $deployment->setServer($server);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn([
                'self' => 'url',
                'lastPush' => 'url',
                'lastSuccessfulPush' => ['href' => 'url']
            ]);
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');

        $this->repoNormalizer
            ->shouldReceive('normalize')
            ->with($repo, ['test1'])
            ->andReturn('normalized-repo');
        $this->serverNormalizer
            ->shouldReceive('normalize')
            ->with($server, ['test2'])
            ->andReturn('normalized-server');

        $normalizer = new DeploymentNormalizer($this->api, $this->url, $this->repoNormalizer, $this->serverNormalizer);
        $actual = $normalizer->normalize($deployment, [
            'repository' => ['test1'],
            'server' => ['test2']
        ]);

        $expected = [
            'id' => '1234',
            'path' => '/server/path',
            'repository' => 'normalized-repo',
            'server' => 'normalized-server',
            '_links' => [
                'self' => 'url',
                'lastPush' => 'url',
                'lastSuccessfulPush' => ['href' => 'url?status=Success']
            ]
        ];

        $this->assertSame($expected, $actual);
    }
}
