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

class PullRequestsApiTest extends PHPUnit_Framework_TestCase
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

    public function testDataIsRetrievedFromServiceAndFormattedCorrectly()
    {
        $openData = [
            [
                'number' => 5,
                'state' => 'open',
                'title' => 'title.test1',
                'html_url' => 'url.test1',
                'base' => [
                    'ref' => 'target.test1',
                    'user' => ['login' => 'target-user.test1']
                ],
                'head' => [
                    'ref' => 'feature.test1',
                    'user' => ['login' => 'feature-user.test1']
                ]
            ],
            [
                'number' => 1,
                'state' => 'open',
                'title' => 'title.test3',
                'html_url' => 'url.test3',
                'base' => [
                    'ref' => 'target.test3',
                    'user' => ['login' => 'target-user.test3']
                ],
                'head' => [
                    'ref' => 'feature.test3',
                    'user' => ['login' => 'feature-user.test3']
                ]
            ]
        ];

        $closedData = [
            [
                'number' => 3,
                'state' => 'open',
                'title' => 'title.test2',
                'html_url' => 'url.test2',
                'base' => [
                    'ref' => 'target.test2',
                    'user' => ['login' => 'target-user.test2']
                ],
                'head' => [
                    'ref' => 'feature.test2',
                    'user' => ['login' => 'feature-user.test2']
                ]
            ]
        ];

        $expectedJson = <<<'JSON'
[
    {
        "from": "feature-user.test1\/feature.test1",
        "number": 5,
        "state": "open",
        "title": "title.test1",
        "to": "target-user.test1\/target.test1",
        "url": "url.test1"
    },
    {
        "from": "feature-user.test2\/feature.test2",
        "number": 3,
        "state": "open",
        "title": "title.test2",
        "to": "target-user.test2\/target.test2",
        "url": "url.test2"
    },
    {
        "from": "feature-user.test3\/feature.test3",
        "number": 1,
        "state": "open",
        "title": "title.test3",
        "to": "target-user.test3\/target.test3",
        "url": "url.test3"
    }
]
JSON;

        $this->githubService
            ->shouldReceive('openPullRequests')
            ->andReturn($openData);
        $this->githubService
            ->shouldReceive('closedPullRequests')
            ->andReturn($closedData);

        $pulls = new PullRequestsApi($this->githubService);
        $pulls($this->request, $this->response, ['username' => 'test-username', 'repository' => 'test-repository']);

        $this->assertSame($expectedJson, $this->response->getBody());
    }
}
