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

class EnvironmentNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
    }

    public function testNormalizationOfLink()
    {
        $environment = new Environment;

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');

        $normalizer = new EnvironmentNormalizer($this->api, $this->url);
        $actual = $normalizer->linked($environment);

        $this->assertSame('link', $actual);
    }

    public function testNormalization()
    {
        $environment = new Environment;
        $environment->setId('1234');
        $environment->setKey('test');
        $environment->setOrder('1');

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');

        $normalizer = new EnvironmentNormalizer($this->api, $this->url);
        $actual = $normalizer->normalize($environment);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'key' => 'test',
            'order' => '1',
            '_links' => [
                'self' => 'link',
                'index' => 'link'
            ]
        ];

        $this->assertSame($expected, $actual);
    }
}
