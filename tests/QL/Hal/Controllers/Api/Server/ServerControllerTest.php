<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Controllers\Api\Server;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Server;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class ServerControllerTest extends PHPUnit_Framework_TestCase
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
//    public function testNoServerFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\ServerRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\ServerNormalizer');
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new ServerController(
//            $api,
//            $repo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testServerFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\ServerRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\ServerNormalizer');
//
//        $server = new Server;
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($server);
//
//        $normalizer
//            ->shouldReceive('normalize')
//            ->with($server)
//            ->andReturn('normalized-server');
//
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, 'normalized-server')
//            ->once();
//
//        $controller = new ServerController(
//            $api,
//            $repo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(200, $this->response->getStatus());
//    }
//}
