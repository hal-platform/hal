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
use QL\Hal\Core\Entity\Repository;

class RepositoryNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');
        $this->url
            ->shouldReceive('githubUserUrl')
            ->andReturn('http://git/user');
        $this->url
            ->shouldReceive('githubRepoUrl')
            ->andReturn('http://git/user/repo');
    }

    public function testNormalizationOfLink()
    {
        $groupNormalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $repo = new Repository;
        $repo->setId('1234');

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');

        $normalizer = new RepositoryNormalizer($this->api, $this->url, $groupNormalizer);
        $actual = $normalizer->linked($repo);

        $this->assertSame('link', $actual);
    }

    public function testNormalizationWithoutCriteria()
    {
        $groupNormalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $group = new Group;
        $group->setId('5678');

        $repo = new Repository;
        $repo->setId('1234');
        $repo->setDescription('testdescription');
        $repo->setEmail('email@test.com');
        $repo->setKey('nickname');
        $repo->setGroup($group);

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
        $groupNormalizer
            ->shouldReceive('linked')
            ->andReturn('linked-group');

        $normalizer = new RepositoryNormalizer($this->api, $this->url, $groupNormalizer);
        $actual = $normalizer->normalize($repo);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'key' => 'nickname',
            'description' => 'testdescription',
            'email' => 'email@test.com',
            'githubUser' => [
                'text' => null,
                'url' => 'http://git/user'
            ],
            'githubRepo' => [
                'text' => null,
                'url' => 'http://git/user/repo'
            ],
            'buildCmd' => null,
            'prePushCmd' => null,
            'postPushCmd' => null,
            '_links' => [
                'self' => 'link',
                'deployments' => 'link',
                'builds' => 'link',
                'pushes' => 'link',
                'tags' => 'link',
                'branches' => 'link',
                'pullRequests' => 'link',
                'index' => 'link',
                'group' => 'linked-group'
            ]
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationCriteriaCascadesToChildEntity()
    {
        $groupNormalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $group = new Group;
        $group->setId('5678');

        $repo = new Repository;
        $repo->setId('1234');
        $repo->setGroup($group);

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
        $groupNormalizer
            ->shouldReceive('normalize')
            ->with($group, ['test1'])
            ->andReturn('embedded-group');

        $normalizer = new RepositoryNormalizer($this->api, $this->url, $groupNormalizer);
        $actual = $normalizer->normalize($repo, ['group' => ['test1']]);

        $expected = [
            'id' => '1234',
            'url' => 'http://hal/page',
            'key' => null,
            'description' => null,
            'email' => null,
            'githubUser' => [
                'text' => null,
                'url' => 'http://git/user'
            ],
            'githubRepo' => [
                'text' => null,
                'url' => 'http://git/user/repo'
            ],
            'buildCmd' => null,
            'prePushCmd' => null,
            'postPushCmd' => null,
            '_links' => [
                'self' => 'link',
                'deployments' => 'link',
                'builds' => 'link',
                'pushes' => 'link',
                'tags' => 'link',
                'branches' => 'link',
                'pullRequests' => 'link',
                'index' => 'link'
            ],
            '_embedded' => [
                'group' => 'embedded-group'
            ]
        ];

        $this->assertSame($expected, $actual);
    }
}
