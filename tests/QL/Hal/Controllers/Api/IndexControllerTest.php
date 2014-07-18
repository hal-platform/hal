<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class IndexControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function test()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $api
            ->shouldReceive('parseLinks')
            ->andReturn('parsed-hal-links');

        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, ['_links' => 'parsed-hal-links'])
            ->once();



        $controller = new IndexController($api);
        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
    }
}
