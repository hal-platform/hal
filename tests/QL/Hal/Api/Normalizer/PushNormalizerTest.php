<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;

/**
 * @todo fix this test
 * @requires function skipthistest
 */
class PushNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;
    public $time;
    public $buildNormalizer;
    public $deploymentNormalizer;
    public $userNormalizer;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $this->time = Mockery::mock('QL\Hal\Helpers\TimeHelper');
        $this->buildNormalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');
        $this->deploymentNormalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
        $this->userNormalizer = Mockery::mock('QL\Hal\Api\UserNormalizer');
    }

    public function testNormalizationOfLinkedResource()
    {
        $push = new Push;
        $push->setId('1234');

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');

        $normalizer = new PushNormalizer(
            $this->api,
            $this->url,
            $this->time,
            $this->buildNormalizer,
            $this->deploymentNormalizer,
            $this->userNormalizer
        );
        $actual = $normalizer->linked($push);

        $this->assertSame('link', $actual);
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
            ->shouldReceive('parseLink')
            ->andReturn('link');
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
            ->shouldReceive('linked')
            ->andReturn('linked-build');
        $this->deploymentNormalizer
            ->shouldReceive('linked')
            ->andReturn('linked-deployment');

        $normalizer = new PushNormalizer(
            $this->api,
            $this->url,
            $this->time,
            $this->buildNormalizer,
            $this->deploymentNormalizer,
            $this->userNormalizer
        );
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
            '_links' => [
                'self' => 'link',
                'logs' => 'link',
                'build' => 'linked-build',
                'deployment' => 'linked-deployment'
            ]
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
            ->shouldReceive('parseLink')
            ->andReturn('link');
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

        $normalizer = new PushNormalizer(
            $this->api,
            $this->url,
            $this->time,
            $this->buildNormalizer,
            $this->deploymentNormalizer,
            $this->userNormalizer
        );
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

            '_links' => [
                'self' => 'link',
                'logs' => 'link'
            ],
            '_embedded' => [
                'build' => 'normalized-build',
                'deployment' => 'normalized-deployment'
            ]
        ];

        $this->assertSame($expected, $actual);
    }
}
