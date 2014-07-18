<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Repository;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class RepositoriesControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoRepositoriesFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\RepositoryNormalizer');

        $repoRepo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturnNull();

        $controller = new RepositoriesController(
            $api,
            $repoRepo,
            $normalizer
        );

        $controller($this->request, $this->response);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testRepositoriesFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\RepositoryNormalizer');

        $repos = [
            new Repository,
            new Repository
        ];

        $repoRepo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturn($repos);

        $normalizer
            ->shouldReceive('normalize')
            ->with($repos[0])
            ->andReturn('normalized-repo1');
        $normalizer
            ->shouldReceive('normalize')
            ->with($repos[1])
            ->andReturn('normalized-repo2');

        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, ['normalized-repo1', 'normalized-repo2'])
            ->once();

        $controller = new RepositoriesController(
            $api,
            $repoRepo,
            $normalizer
        );

        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
    }
}
