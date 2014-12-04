<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Controllers\Api\Deployment;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Deployment;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class DeploymentControllerTest extends PHPUnit_Framework_TestCase
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
//    public function testNoDeploymentFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new DeploymentController(
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
//    public function testDeploymentFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
//
//        $deployment = new Deployment;
//
//        $repo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($deployment);
//
//        $normalizer
//            ->shouldReceive('normalize')
//            ->with($deployment)
//            ->andReturn('normalized-deployment');
//
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, 'normalized-deployment')
//            ->once();
//
//        $controller = new DeploymentController(
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
