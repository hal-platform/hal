<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class PushesControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoRepositoryFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $pushRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturnNull();

        $controller = new PushesController(
            $api,
            $repoRepo,
            $pushRepo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testNoPushesFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $pushRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');

        $repo = new Repository;

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($repo);

        $pushRepo
            ->shouldReceive('getForRepository')
            ->with($repo)
            ->andReturnNull();

        $controller = new PushesController(
            $api,
            $repoRepo,
            $pushRepo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testPushesFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $pushRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');

        $repo = new Repository;
        $pushes = [
            new Push,
            new Push
        ];

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($repo);

        $pushRepo
            ->shouldReceive('getForRepository')
            ->with($repo)
            ->andReturn($pushes);

        $normalizer
            ->shouldReceive('normalizeLinked')
            ->with($pushes[0])
            ->andReturn('normalized-push1');
        $normalizer
            ->shouldReceive('normalizeLinked')
            ->with($pushes[1])
            ->andReturn('normalized-push2');

        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, ['normalized-push1', 'normalized-push2'])
            ->once();

        $controller = new PushesController(
            $api,
            $repoRepo,
            $pushRepo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(200, $this->response->getStatus());
    }
}