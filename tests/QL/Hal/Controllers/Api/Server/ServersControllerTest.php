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
//class ServersControllerTest extends PHPUnit_Framework_TestCase
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
//    public function testNoServersFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\ServerRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\ServerNormalizer');
//
//        $repo
//            ->shouldReceive('findBy')
//            ->with([], Mockery::type('array'))
//            ->andReturnNull();
//
//        $controller = new ServersController($api, $repo, $normalizer);
//        $controller($this->request, $this->response);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testServersFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\ServerRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\ServerNormalizer');
//
//        $servers = [
//            new Server,
//            new Server
//        ];
//
//        $repo
//            ->shouldReceive('findBy')
//            ->with([], Mockery::type('array'))
//            ->andReturn($servers);
//
//        $normalizer
//            ->shouldReceive('linked')
//            ->with($servers[0])
//            ->andReturn('linked-server1');
//        $normalizer
//            ->shouldReceive('linked')
//            ->with($servers[1])
//            ->andReturn('linked-server2');
//
//        $api
//            ->shouldReceive('parseLink')
//            ->andReturn('self-link');
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, $this->storeExpectation($content))
//            ->once();
//
//        $controller = new ServersController($api, $repo, $normalizer);
//        $controller($this->request, $this->response);
//
//        $this->assertSame(200, $this->response->getStatus());
//        $this->assertSame(2, $content['count']);
//        $this->assertSame('self-link', $content['_links']['self']);
//        $this->assertCount(2, $content['_links']['servers']);
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
