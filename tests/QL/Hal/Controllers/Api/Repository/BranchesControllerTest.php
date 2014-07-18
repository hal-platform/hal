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

class BranchesControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testRepositoryNotFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $github = Mockery::mock('QL\Hal\Services\GithubService');

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturnNull();

        $controller = new BranchesController($api, $url, $github, $repoRepo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testNoBranchesFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $github = Mockery::mock('QL\Hal\Services\GithubService');

        $repo = new Repository;

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($repo);

        $github
            ->shouldReceive('branches')
            ->andReturnNull();

        $controller = new BranchesController($api, $url, $github, $repoRepo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testBranchesFoundAndSorted()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $github = Mockery::mock('QL\Hal\Services\GithubService');

        $repo = new Repository;
        $branches = [
            [
                'name' => 'abc',
                'object' => ['sha' => '1234']
            ],
            [
                'name' => '928abc',
                'object' => ['sha' => '5678']
            ],
            [
                'name' => 'master',
                'object' => ['sha' => '0000']
            ],
            [
                'name' => '123',
                'object' => ['sha' => '9999']
            ]
        ];

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($repo);

        $github
            ->shouldReceive('branches')
            ->andReturn($branches);

        $url
            ->shouldReceive('formatGitReference');
        $url
            ->shouldReceive('githubTreeUrl');

        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content))
            ->once();

        $controller = new BranchesController($api, $url, $github, $repoRepo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(200, $this->response->getStatus());

        // Verify the order of branches
        $this->assertSame('master', $content[0]['name']);
        $this->assertSame('123', $content[1]['name']);
        $this->assertSame('928abc', $content[2]['name']);
        $this->assertSame('abc', $content[3]['name']);
    }

    public function storeExpectation(&$stored)
    {
        return Mockery::on(function($v) use (&$stored) {
            $stored = $v;
            return true;
        });
    }
}
