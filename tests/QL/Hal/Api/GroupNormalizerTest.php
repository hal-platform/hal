<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Group;

class GroupNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
    }

    public function testNormalizationOfLinkedResource()
    {
        $group = new Group;
        $group->setId('1234');

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');

        $normalizer = new GroupNormalizer($this->api, $this->url);
        $actual = $normalizer->normalizeLinked($group);

        $expected = [
            'id' => '1234',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalization()
    {
        $group = new Group;
        $group->setId('1234');
        $group->setKey('test');
        $group->setName('testgroup');

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');

        $normalizer = new GroupNormalizer($this->api, $this->url);
        $actual = $normalizer->normalize($group);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'key' => 'test',
            'name' => 'testgroup',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }
}
