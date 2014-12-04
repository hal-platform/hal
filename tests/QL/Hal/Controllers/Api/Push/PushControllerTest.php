<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Controllers\Api\Push;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Push;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class PushControllerTest extends PHPUnit_Framework_TestCase
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
//    public function testNoPushFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new PushController(
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
//    public function testPushFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $push = new Push;
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($push);
//
//        $normalizer
//            ->shouldReceive('normalize')
//            ->with($push)
//            ->andReturn('normalized-push');
//
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, 'normalized-push')
//            ->once();
//
//        $controller = new PushController(
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
