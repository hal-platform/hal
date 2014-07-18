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
            ->shouldReceive('linked')
            ->with($repos[0])
            ->andReturn('linked-repo1');
        $normalizer
            ->shouldReceive('linked')
            ->with($repos[1])
            ->andReturn('linked-repo2');

        $api
            ->shouldReceive('parseLink')
            ->andReturn('self-link');
        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content))
            ->once();

        $controller = new RepositoriesController(
            $api,
            $repoRepo,
            $normalizer
        );

        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
        $this->assertSame(2, $content['count']);
        $this->assertSame('self-link', $content['_links']['self']);
        $this->assertCount(2, $content['_links']['repositories']);
    }

    public function storeExpectation(&$stored)
    {
        return Mockery::on(function($v) use (&$stored) {
            $stored = $v;
            return true;
        });
    }
}
