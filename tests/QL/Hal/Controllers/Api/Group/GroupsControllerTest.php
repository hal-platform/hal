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
            ->shouldReceive('linked')
            ->with($groups[0])
            ->andReturn('linked-group1');
        $normalizer
            ->shouldReceive('linked')
            ->with($groups[1])
            ->andReturn('linked-group2');
        $api
            ->shouldReceive('parseLink')
            ->andReturn('self-link');
        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content))
            ->once();

        $controller = new GroupsController($api, $repo, $normalizer);
        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
        $this->assertSame(2, $content['count']);
        $this->assertSame('self-link', $content['_links']['self']);
        $this->assertCount(2, $content['_links']['groups']);
    }

    public function storeExpectation(&$stored)
    {
        return Mockery::on(function($v) use (&$stored) {
            $stored = $v;
            return true;
        });
    }
}
