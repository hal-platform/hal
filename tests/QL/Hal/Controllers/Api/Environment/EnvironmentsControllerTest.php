<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Environment;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Environment;
use Slim\Environment as SlimEnvironment;
use Slim\Http\Request;
use Slim\Http\Response;

class EnvironmentsControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(SlimEnvironment::mock());
        $this->response = new Response;
    }

    public function testNoEnvironmentsFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\EnvironmentRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\EnvironmentNormalizer');

        $repo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturnNull();

        $controller = new EnvironmentsController($api, $repo, $normalizer);
        $controller($this->request, $this->response);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testEnvironmentsFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\EnvironmentRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\EnvironmentNormalizer');

        $envs = [
            new Environment,
            new Environment
        ];

        $repo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturn($envs);
        $normalizer
            ->shouldReceive('normalize')
            ->with($envs[0])
            ->andReturn('normalized-env1');
        $normalizer
            ->shouldReceive('normalize')
            ->with($envs[1])
            ->andReturn('normalized-env2');
        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, ['normalized-env1', 'normalized-env2'])
            ->once();

        $controller = new EnvironmentsController($api, $repo, $normalizer);
        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
    }
}
