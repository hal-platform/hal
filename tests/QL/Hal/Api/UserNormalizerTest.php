<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use MCP\DataType\HttpUrl;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\User;

class UserNormalizerTest extends PHPUnit_Framework_TestCase
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
        $user = new User;
        $user->setId('1234');

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');

        $normalizer = new UserNormalizer($this->api, $this->url);
        $actual = $normalizer->linked($user);

        $this->assertSame('link', $actual);
    }

    public function testNormalization()
    {
        $user = new User;
        $user->setId('1234');
        $user->setPictureUrl(HttpUrl::create('http://picture/url'));

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');

        $normalizer = new UserNormalizer($this->api, $this->url);
        $actual = $normalizer->normalize($user);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'handle' => null,
            'name' => null,
            'email' => null,
            'picture' => 'http://picture/url',
            '_links' => [
                'self' => 'link',
                'index' => 'link'
            ]
        ];

        $this->assertSame($expected, $actual);
    }
}
