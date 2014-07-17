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
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;

class BuildNormalizerTest extends PHPUnit_Framework_TestCase
{
    public $api;
    public $url;
    public $time;
    public $envNormalizer;
    public $repoNormalizer;
    public $userNormalizer;

    public function setUp()
    {
        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $this->time = Mockery::mock('QL\Hal\Helpers\TimeHelper');
        $this->envNormalizer = Mockery::mock('QL\Hal\Api\EnvironmentNormalizer');
        $this->repoNormalizer = Mockery::mock('QL\Hal\Api\RepositoryNormalizer');
        $this->userNormalizer = Mockery::mock('QL\Hal\Api\UserNormalizer');

        $this->url
            ->shouldReceive('urlFor')
            ->andReturn('http://hal/page');
        $this->url
            ->shouldReceive('githubReferenceUrl')
            ->andReturn('http://git/ref');
        $this->url
            ->shouldReceive('githubCommitUrl')
            ->andReturn('http://git/commit');
    }

    public function testNormalizationOfLinkedResource()
    {
        $build = new Build;
        $build->setId('1234');

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');

        $normalizer = new BuildNormalizer(
            $this->api,
            $this->url,
            $this->time,
            $this->envNormalizer,
            $this->repoNormalizer,
            $this->userNormalizer
        );
        $actual = $normalizer->normalizeLinked($build);

        $expected = [
            'id' => '1234',
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationWithoutCriteria()
    {
        $env = new Environment;
        $repo = new Repository;
        $user = new User;

        $build = new Build;
        $build->setId('1234');
        $build->setEnvironment($env);
        $build->setRepository($repo);
        $build->setUser($user);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->time
            ->shouldReceive('relative')
            ->andReturn('right now');
        $this->time
            ->shouldReceive('format')
            ->andReturn('');

        $this->envNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-env');
        $this->repoNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-repo');
        $this->userNormalizer
            ->shouldReceive('normalizeLinked')
            ->andReturn('normalized-user');

        $normalizer = new BuildNormalizer(
            $this->api,
            $this->url,
            $this->time,
            $this->envNormalizer,
            $this->repoNormalizer,
            $this->userNormalizer
        );
        $actual = $normalizer->normalize($build);

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
            'reference' => [
                'text' => null,
                'url' => 'http://git/ref'
            ],
            'commit' => [
                'text' => null,
                'url' => 'http://git/commit'
            ],
            'environment' => 'normalized-env',
            'repository' => 'normalized-repo',
            'initiator' => [
                'user' => 'normalized-user',
                'consumer' => null
            ],
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }

    public function testNormalizationCriteriaCascadesToChildEntity()
    {
        $env = new Environment;
        $repo = new Repository;

        $build = new Build;
        $build->setId('1234');
        $build->setEnvironment($env);
        $build->setRepository($repo);

        $this->api
            ->shouldReceive('parseLinks')
            ->andReturn('links');
        $this->time
            ->shouldReceive('relative')
            ->andReturn('right now');
        $this->time
            ->shouldReceive('format')
            ->andReturn('');

        $this->envNormalizer
            ->shouldReceive('normalize')
            ->with($env, ['test1'])
            ->andReturn('normalized-env');
        $this->repoNormalizer
            ->shouldReceive('normalize')
            ->with($repo, ['test2'])
            ->andReturn('normalized-repo');

        $normalizer = new BuildNormalizer(
            $this->api,
            $this->url,
            $this->time,
            $this->envNormalizer,
            $this->repoNormalizer,
            $this->userNormalizer
        );
        $actual = $normalizer->normalize($build, [
            'environment' => ['test1'],
            'repository' => ['test2']
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
            'reference' => [
                'text' => null,
                'url' => 'http://git/ref'
            ],
            'commit' => [
                'text' => null,
                'url' => 'http://git/commit'
            ],
            'environment' => 'normalized-env',
            'repository' => 'normalized-repo',
            'initiator' => [
                'user' => null,
                'consumer' => null
            ],
            '_links' => 'links'
        ];

        $this->assertSame($expected, $actual);
    }
}
