<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Build;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class LogControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoBuildFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');

        $repo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturnNull();

        $controller = new LogController($api, $repo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testLogNotFoundBecauseNotImplemented()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');

        $build = new Build;

        $repo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($build);

        $controller = new LogController($api, $repo);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }
}
