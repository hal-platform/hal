<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Github;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class ReposApiTest extends PHPUnit_Framework_TestCase
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

    public function testCalledWithoutParamsBombsOut()
    {
        $repos = new ReposApi($this->githubService);
        $repos($this->request, $this->response);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testCalledWithoutCorrectParametersBombsOut()
    {
        $repos = new ReposApi($this->githubService);
        $repos($this->request, $this->response, []);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testCalledWithEmptyParametersBombsOut()
    {
        $repos = new ReposApi($this->githubService);
        $repos($this->request, $this->response, ['username' => '']);

        $this->assertSame(400, $this->response->getStatus());
    }

    public function testDataIsRetrievedFromService()
    {
        $apiData = [
            [
                'name' => 'repo5',
                'full_name' => 'testuser/repo5',
                'description' => 'description here',
                'otherdata' => 'isthrownaway'
            ],
            [
                'name' => 'repo1',
                'full_name' => 'testuser/repo1',
                'description' => '',
                'otherdata' => 'isthrownaway'
            ]
        ];
        $expectedJson = <<<'JSON'
{
    "repo1": "repo1",
    "repo5": "repo5 (description here)"
}
JSON;

        $this->githubService
            ->shouldReceive('repositories')
            ->with('testuser')
            ->andReturn($apiData);

        $repos = new ReposApi($this->githubService);
        $repos($this->request, $this->response, ['username' => 'testuser']);

        $this->assertSame($expectedJson, $this->response->getBody());
    }
}
