<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class BuildsControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoRepoFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $buildRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'repo-id'])
            ->andReturnNull();

        $controller = new BuildsController(
            $api,
            $repoRepo,
            $buildRepo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'repo-id']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testNoBuildsFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $buildRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');

        $repo = new Repository;

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'repo-id'])
            ->andReturn($repo);

        $buildRepo
            ->shouldReceive('findBy')
            ->with(['repository' => $repo], Mockery::type('array'))
            ->andReturn([]);

        $controller = new BuildsController(
            $api,
            $repoRepo,
            $buildRepo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'repo-id']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testBuildsFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $buildRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');

        $repo = new Repository;
        $builds = [
            new Build,
            new Build
        ];

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'repo-id'])
            ->andReturn($repo);

        $buildRepo
            ->shouldReceive('findBy')
            ->with(['repository' => $repo], Mockery::type('array'))
            ->andReturn($builds);

        $normalizer
            ->shouldReceive('linked')
            ->with($builds[0])
            ->andReturn('linked-build1');

        $normalizer
            ->shouldReceive('linked')
            ->with($builds[1])
            ->andReturn('linked-build2');

        $api
            ->shouldReceive('parseLink')
            ->andReturn('self-link');
        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content))
            ->once();

        $controller = new BuildsController(
            $api,
            $repoRepo,
            $buildRepo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'repo-id']);

        $this->assertSame(200, $this->response->getStatus());
        $this->assertSame(2, $content['count']);
        $this->assertSame('self-link', $content['_links']['self']);
        $this->assertCount(2, $content['_links']['builds']);
    }

    public function storeExpectation(&$stored)
    {
        return Mockery::on(function($v) use (&$stored) {
            $stored = $v;
            return true;
        });
    }
}
