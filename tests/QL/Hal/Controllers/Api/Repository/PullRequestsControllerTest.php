<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Controllers\Api\Repository;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Repository;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class PullRequestsControllerTest extends PHPUnit_Framework_TestCase
//{
//    public $request;
//    public $response;
//
//    public function setUp()
//    {
//        $this->request = new Request(Environment::mock());
//        $this->response = new Response;
//    }
//
//    public function testRepositoryNotFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
//        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
//        $github = Mockery::mock('QL\Hal\Services\GithubService');
//
//        $repoRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new PullRequestsController($api, $url, $github, $repoRepo);
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testNoPullRequestsFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
//        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
//        $github = Mockery::mock('QL\Hal\Services\GithubService');
//
//        $repo = new Repository;
//
//        $repoRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($repo);
//
//        $github
//            ->shouldReceive('openPullRequests')
//            ->andReturn([]);
//
//        $github
//            ->shouldReceive('closedPullRequests')
//            ->andReturn([]);
//
//        $controller = new PullRequestsController($api, $url, $github, $repoRepo);
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testPullRequestsFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $url = Mockery::mock('QL\Hal\Helpers\UrlHelper');
//        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
//        $github = Mockery::mock('QL\Hal\Services\GithubService');
//
//        $repo = new Repository;
//
//        $open = [
//            [
//                'title' => 'test1',
//                'state' => 'open',
//                'number' => '1',
//                'diff_url' => 'http://git/pull/1',
//                'base' => [
//                    'sha' => '123',
//                    'ref' => 'branchname',
//                    'user' => ['login' => 'testuser1']
//                ],
//                'head' => [
//                    'sha' => '456',
//                    'ref' => 'branchname',
//                    'user' => ['login' => 'testuser2']
//                ]
//            ],
//            [
//                'title' => 'test2',
//                'state' => 'open',
//                'number' => '2',
//                'diff_url' => 'http://git/pull/1',
//                'base' => [
//                    'sha' => '123',
//                    'ref' => 'branchname',
//                    'user' => ['login' => 'testuser1']
//                ],
//                'head' => [
//                    'sha' => '456',
//                    'ref' => 'branchname',
//                    'user' => ['login' => 'testuser2']
//                ]
//            ]
//        ];
//
//        $closed = [
//            [
//                'title' => 'test3',
//                'state' => 'closed',
//                'number' => '3',
//                'diff_url' => 'http://git/pull/1',
//                'base' => [
//                    'sha' => '123',
//                    'ref' => 'branchname',
//                    'user' => ['login' => 'testuser1']
//                ],
//                'head' => [
//                    'sha' => '456',
//                    'ref' => 'branchname',
//                    'user' => ['login' => 'testuser2']
//                ]
//            ]
//        ];
//
//        $repoRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($repo);
//
//        $github
//            ->shouldReceive('openPullRequests')
//            ->andReturn($open);
//        $github
//            ->shouldReceive('closedPullRequests')
//            ->andReturn($closed);
//
//        $url
//            ->shouldReceive('formatGitReference');
//        $url
//            ->shouldReceive('githubPullRequestUrl');
//
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, $this->storeExpectation($content))
//            ->once();
//
//        $controller = new PullRequestsController($api, $url, $github, $repoRepo);
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(200, $this->response->getStatus());
//
//        $this->assertSame('test1', $content['open'][0]['title']);
//        $this->assertSame('test2', $content['open'][1]['title']);
//        $this->assertSame('test3', $content['closed'][0]['title']);
//    }
//
//    public function storeExpectation(&$stored)
//    {
//        return Mockery::on(function($v) use (&$stored) {
//            $stored = $v;
//            return true;
//        });
//    }
//}
