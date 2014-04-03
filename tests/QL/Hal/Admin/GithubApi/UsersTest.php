<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin\GithubApi;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersTest extends PHPUnit_Framework_TestCase
{
    public $githubService;
    public $session;
    public $request;
    public $response;

    public function setUp()
    {
        $this->githubService = Mockery::mock('QL\Hal\GithubApi\HackUser');
        $this->session = Mockery::mock('QL\Hal\Session');

        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testCachedDataIsRetrievedFromSessionIfPresent()
    {
        $this->session
            ->shouldReceive('has')
            ->with('github-users') // assert that the key is constructed correctly
            ->andReturn(true);
        $this->session
            ->shouldReceive('get')
            ->andReturn('blahblahblah');

        $users = new Users($this->githubService, $this->session);
        $users($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
        $this->assertContains('application/json', $this->response->headers()['Content-Type']);
        $this->assertSame('blahblahblah', $this->response->getBody());
    }

    public function testCachedDataIsRetrievedFromService()
    {
        $apiData = [
            'users' => [
                [
                    'login' => 'm-USERname',
                    'repos' => 8,
                    'otherdata' => 'isthrownaway'
                ],
                [
                    'login' => 'a-username',
                    'repos' => 0,
                    'description' => 'description here',
                    'otherdata' => 'isthrownaway'
                ],
                [
                    'login' => 'z-username',
                    'repos' => '2'
                ]
            ]
        ];
        $expectedJson = <<<'JSON'
{
    "a-username": "a-username (0)",
    "m-username": "m-USERname (8)",
    "z-username": "z-username (2)"
}
JSON;

        $this->session
            ->shouldReceive('has')
            ->andReturn(false);
        $this->session
            ->shouldReceive('set')
            ->once();

        $this->githubService
            ->shouldReceive('find')
            ->andReturn($apiData);

        $users = new Users($this->githubService, $this->session);
        $users($this->request, $this->response);

        $this->assertSame($expectedJson, $this->response->getBody());
    }
}
