<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;

class PushNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;
    public $time;
    public $buildNormalizer;
    public $deploymentNormalizer;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $this->time = Mockery::mock('QL\Hal\Helpers\TimeHelper');
        $this->buildNormalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');
        $this->deploymentNormalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
    }

    public function testNormalizationOfLinkedResource()
    {
        $push = new Push;
        $push->setId('1234');

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');

        $normalizer = new PushNormalizer($this->api, $this->url, $this->time, $this->buildNormalizer, $this->deploymentNormalizer);
        $actual = $normalizer->normalizeLinked($push);

        $expected = [
            'id' => '1234',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationWithoutCriteria()
    {
        $build = new Build;
        $deployment = new Deployment;

        $push = new Push;
        $push->setId('1234');
        $push->setBuild($build);
        $push->setDeployment($deployment);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');
        $this->time
            ->shouldReceive('relative')
            ->andReturn('right now');
        $this->time
            ->shouldReceive('format')
            ->andReturn('');

        $this->buildNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-build');
        $this->deploymentNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-deployment');

        $normalizer = new PushNormalizer($this->api, $this->url, $this->time, $this->buildNormalizer, $this->deploymentNormalizer);
        $actual = $normalizer->normalize($push);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'status' => null,
            'created' => [
                'text' => 'right now',
                'datetime' => ''
            ],
            'start' => [
                'text' => 'right now',
                'datetime' => ''
            ],
            'end' => [
                'text' => 'right now',
                'datetime' => ''
            ],
            'build' => 'normalized-build',
            'deployment' => 'normalized-deployment',
            'initiator' => [
                'user' => null,
                'consumer' => null
            ],
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationCriteriaCascadesToChildEntity()
    {
        $build = new Build;
        $deployment = new Deployment;

        $push = new Push;
        $push->setId('1234');
        $push->setBuild($build);
        $push->setDeployment($deployment);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');
        $this->time
            ->shouldReceive('relative')
            ->andReturn('right now');
        $this->time
            ->shouldReceive('format')
            ->andReturn('');

        $this->buildNormalizer
            ->shouldReceive('normalize')
            ->with($build, ['test1'])
            ->andReturn('normalized-build');
        $this->deploymentNormalizer
            ->shouldReceive('normalize')
            ->with($deployment, ['test2'])
            ->andReturn('normalized-deployment');

        $normalizer = new PushNormalizer($this->api, $this->url, $this->time, $this->buildNormalizer, $this->deploymentNormalizer);
        $actual = $normalizer->normalize($push, [
            'build' => ['test1'],
            'deployment' => ['test2']
        ]);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'status' => null,
            'created' => [
                'text' => 'right now',
                'datetime' => ''
            ],
            'start' => [
                'text' => 'right now',
                'datetime' => ''
            ],
            'end' => [
                'text' => 'right now',
                'datetime' => ''
            ],
            'build' => 'normalized-build',
            'deployment' => 'normalized-deployment',
            'initiator' => [
                'user' => null,
                'consumer' => null
            ],
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }
}
