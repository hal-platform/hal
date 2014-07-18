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

    public function testNormalizationOfLink()
    {
        $group = new Group;
        $group->setId('1234');

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');

        $normalizer = new GroupNormalizer($this->api, $this->url);
        $actual = $normalizer->linked($group);

        $this->assertSame('link', $actual);
    }

    public function testNormalization()
    {
        $group = new Group;
        $group->setId('1234');
        $group->setKey('test');
        $group->setName('testgroup');

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
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
            '_links' => [
                'self' => 'link',
                'index' => 'link'
            ]
        ];

        $this->assertSame($expected, $actual);
    }
}
