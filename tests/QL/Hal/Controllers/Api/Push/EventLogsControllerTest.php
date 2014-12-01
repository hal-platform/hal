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
use QL\Panthor\Testing\MockeryAssistantTrait;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class EventLogsControllerTest extends PHPUnit_Framework_TestCase
{
    use MockeryAssistantTrait;

    public $request;
    public $response;
    public $api;
    public $repo;
    public $normalizer;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;

        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->repo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
        $this->normalizer = Mockery::mock('QL\Hal\Api\EventLogNormalizer');
    }

    public function testNoPushFound()
    {
        $this->repo
            ->shouldReceive('find')
            ->with('test')
            ->andReturnNull();

        $controller = new EventLogsController($this->api, $this->repo, $this->normalizer);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testNoLogsOnPushFound()
    {
        $build = new Push;

        $this->repo
            ->shouldReceive('find')
            ->with('test')
            ->andReturn($build);

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');

        $this->spy($this->api, 'prepareResponse', [Mockery::any(), $this->buildSpy('api')]);

        $controller = new EventLogsController($this->api, $this->repo, $this->normalizer);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());

        $output = call_user_func($this->getSpy('api'));

        $this->assertSame(0, $output['count']);
    }
}
