<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Group;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Group;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class GroupControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoGroupFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\GroupRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $repo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturnNull();

        $controller = new GroupController($api, $repo, $normalizer);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testGroupFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\GroupRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $group = new Group;

        $repo
            ->shouldReceive('findOneBy')
            ->with(['id' => 'test'])
            ->andReturn($group);
        $normalizer
            ->shouldReceive('normalize')
            ->with($group)
            ->andReturn('normalized-group');
        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, 'normalized-group')
            ->once();

        $controller = new GroupController($api, $repo, $normalizer);
        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(200, $this->response->getStatus());
    }
}
