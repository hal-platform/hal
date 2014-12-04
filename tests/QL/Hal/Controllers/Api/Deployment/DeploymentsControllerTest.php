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
//use QL\Hal\Core\Entity\Repository;
//use Slim\Environment;
//use Slim\Http\Request;
//use Slim\Http\Response;
//
//class DeploymentsControllerTest extends PHPUnit_Framework_TestCase
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
//    public function testNoRepoFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
//
//        $repoRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturnNull();
//
//        $controller = new DeploymentsController(
//            $api,
//            $repoRepo,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testNoDeploymentsFound()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
//
//        $repo = new Repository;
//        $repoRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($repo);
//
//        $deploymentRepo
//            ->shouldReceive('findBy')
//            ->with(['repository' => $repo], Mockery::type('array'))
//            ->andReturn([]);
//
//        $controller = new DeploymentsController(
//            $api,
//            $repoRepo,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(404, $this->response->getStatus());
//    }
//
//    public function testDeploymentsFoundAndNormalized()
//    {
//        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $repoRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\RepositoryRepository');
//        $deploymentRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\DeploymentRepository');
//        $normalizer = Mockery::mock('QL\Hal\Api\DeploymentNormalizer');
//
//        $repo = new Repository;
//        $deployments = [
//            new Deployment,
//            new Deployment
//        ];
//
//        $repoRepo
//            ->shouldReceive('findOneBy')
//            ->with(['id' => 'test'])
//            ->andReturn($repo);
//
//        $deploymentRepo
//            ->shouldReceive('findBy')
//            ->with(['repository' => $repo], Mockery::type('array'))
//            ->andReturn($deployments);
//
//        $normalizer
//            ->shouldReceive('linked')
//            ->with($deployments[0])
//            ->andReturn('linked-deployment1');
//
//        $normalizer
//            ->shouldReceive('linked')
//            ->with($deployments[1])
//            ->andReturn('linked-deployment2');
//
//        $api
//            ->shouldReceive('parseLink')
//            ->andReturn('self-link');
//        $api
//            ->shouldReceive('prepareResponse')
//            ->with($this->response, $this->storeExpectation($content))
//            ->once();
//
//        $controller = new DeploymentsController(
//            $api,
//            $repoRepo,
//            $deploymentRepo,
//            $normalizer
//        );
//
//        $controller($this->request, $this->response, ['id' => 'test']);
//
//        $this->assertSame(200, $this->response->getStatus());
//        $this->assertSame(2, $content['count']);
//        $this->assertSame('self-link', $content['_links']['self']);
//        $this->assertCount(2, $content['_links']['deployments']);
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
