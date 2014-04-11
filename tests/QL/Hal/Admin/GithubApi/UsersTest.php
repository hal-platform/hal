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
    public $request;
    public $response;

    public function setUp()
    {
        $this->githubService = Mockery::mock('QL\Hal\Services\GithubService');

        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testDataIsRetrievedFromService()
    {
        $apiData = [
            [
                'login' => 'm-USERname',
                'type' => 'User',
                'otherdata' => 'isthrownaway'
            ],
            [
                'login' => 'a-username',
                'type' => 'Organization',
                'otherdata' => 'isthrownaway'
            ],
            [
                'login' => 'z-username',
                'type' => 'User'
            ]
        ];
        $expectedJson = <<<'JSON'
{
    "users": {
        "m-username": "m-USERname",
        "z-username": "z-username"
    },
    "organizations": {
        "a-username": "a-username"
    }
}
JSON;

        $this->githubService
            ->shouldReceive('getUsers')
            ->andReturn($apiData);

        $users = new Users($this->githubService);
        $users($this->request, $this->response);

        $this->assertSame($expectedJson, $this->response->getBody());
    }
}
