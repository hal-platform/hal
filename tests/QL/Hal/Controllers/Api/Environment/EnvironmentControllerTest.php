<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Controllers\Api\Environment;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Environment;
//use Slim\Environment as SlimEnvironment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class EnvironmentControllerTest extends PHPUnit_Framework_TestCase
//{
//    public $request;
//    public $response;
//
//    public function setUp()
//    {
//        $this->request = new Request(SlimEnvironment::mock());
//        $this->response = new Response;
//    }
//
//    public function testNoEnvironmentFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\EnvironmentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\EnvironmentNormalizer');
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new EnvironmentController($api, $repo, $normalizer);
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testEnvironmentFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\EnvironmentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\EnvironmentNormalizer');
//
//        $env = new Environment;
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($env);
//        $normalizer
//            ->shouldReceive('normalize')
//            ->with($env)
//            ->andReturn('normalized-env');
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, 'normalized-env')
//            ->once();
//
//        $controller = new EnvironmentController($api, $repo, $normalizer);
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(200, $this->response->getStatus());
//    }
//}
