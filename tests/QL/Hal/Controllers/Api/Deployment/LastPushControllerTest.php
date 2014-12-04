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
//use QL\Hal\Core\Entity\Push;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class LastPushControllerTest extends PHPUnit_Framework_TestCase
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
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $deploymentRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new LastPushController(
//            $api,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testInvalidStatusSpecified()
//    {
//        $this->request = new Request(Environment::mock([
//            'QUERY_STRING' => 'status=derp'
//        ]));
//
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $deployment = new Deployment;
//
//        $deploymentRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($deployment);
//
//        $controller = new LastPushController(
//            $api,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(400, $this->response->getStatus());
//    }
//
//    public function testGettingLastPushWithoutSpecifyingStatus()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $deployment = new Deployment;
//        $push = new Push;
//
//        $deploymentRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($deployment);
//
//        $deploymentRepo
//            ->shouldReceive('getLastPush')
//            ->with($deployment)
//            ->andReturn($push)
//            ->once();
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
//        $controller = new LastPushController(
//            $api,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(200, $this->response->getStatus());
//    }
//
//    public function testNoPushFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $deployment = new Deployment;
//
//        $deploymentRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($deployment);
//
//        $deploymentRepo
//            ->shouldReceive('getLastPush')
//            ->with($deployment)
//            ->andReturnNull();
//
//        $controller = new LastPushController(
//            $api,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testGettingLastSuccessfulPush()
//    {
//        $this->request = new Request(Environment::mock([
//            'QUERY_STRING' => 'status=Success'
//        ]));
//
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
//
//        $deployment = new Deployment;
//        $push = new Push;
//
//        $deploymentRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($deployment);
//
//        $deploymentRepo
//            ->shouldReceive('getLastSuccessfulPush')
//            ->with($deployment)
//            ->andReturn($push)
//            ->once();
//
//        $normalizer
//            ->shouldReceive('normalize');
//        $api
//            ->shouldReceive('prepareResponse');
//
//        $controller = new LastPushController(
//            $api,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(200, $this->response->getStatus());
//    }
//}
