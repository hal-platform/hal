<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Controllers\Api\Build;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Build;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class BuildControllerTest extends PHPUnit_Framework_TestCase
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
//    public function testNoBuildFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new BuildController(
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
//    public function testBuildFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');
//
//        $build = new Build;
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($build);
//
//        $normalizer
//            ->shouldReceive('normalize')
//            ->with($build)
//            ->andReturn('normalized-build');
//
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, 'normalized-build')
//            ->once();
//
//        $controller = new BuildController(
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
