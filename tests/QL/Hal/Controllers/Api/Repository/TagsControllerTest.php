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

class TagsControllerTest extends PHPUnit_Framework_TestCase
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

        $controller = new TagsController($api, $url, $github, $repoRepo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testNoTagsFound()
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
            ->shouldReceive('tags')
            ->andReturnNull();

        $controller = new TagsController($api, $url, $github, $repoRepo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testTagsFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
        $github = Mockery::mock('QL\Hal\Services\GithubService');

        $repo = new Repository;
        $tags = [
            [
                'name' => 'tag3',
                'object' => ['sha' => '1234']
            ],
            [
                'name' => 'tag1',
                'object' => ['sha' => '5678']
            ],
            [
                'name' => 'tag2',
                'object' => ['sha' => '9999']
            ]
        ];

        $repoRepo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($repo);

        $github
            ->shouldReceive('tags')
            ->andReturn($tags);

        $url
            ->shouldReceive('formatGitReference');
        $url
            ->shouldReceive('githubTreeUrl');

        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content))
            ->once();

        $controller = new TagsController($api, $url, $github, $repoRepo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(200, $this->response->getStatus());

        // Verify the order of branches
        $this->assertSame('tag/tag3', $content[0]['reference']);
        $this->assertSame('tag/tag1', $content[1]['reference']);
        $this->assertSame('tag/tag2', $content[2]['reference']);
    }

    public function storeExpectation(&$stored)
    {
        return Mockery::on(function($v) use (&$stored) {
            $stored = $v;
            return true;
        });
    }
}
