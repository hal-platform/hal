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

class GroupsControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoGroupsFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\GroupRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $repo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturnNull();

        $controller = new GroupsController($api, $repo, $normalizer);
        $controller($this->request, $this->response);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testGroupsFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\GroupRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\GroupNormalizer');

        $groups = [
            new Group,
            new Group
        ];

        $repo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturn($groups);
        $normalizer
            ->shouldReceive('normalize')
            ->with($groups[0])
            ->andReturn('normalized-group1');
        $normalizer
            ->shouldReceive('normalize')
            ->with($groups[1])
            ->andReturn('normalized-group2');
        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, ['normalized-group1', 'normalized-group2'])
            ->once();

        $controller = new GroupsController($api, $repo, $normalizer);
        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
    }
}
