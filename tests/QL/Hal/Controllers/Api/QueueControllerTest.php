<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\Common\Collections\ArrayCollection;
use MCP\DataType\Time\TimePoint;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class QueueControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public $twig;
    public $layout;
    public $buildRepo;
    public $pushRepo;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;

        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $this->buildNormalizer = Mockery::mock('QL\Hal\Api\BuildNormalizer');
        $this->pushNormalizer = Mockery::mock('QL\Hal\Api\PushNormalizer');
        $this->buildRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
        $this->pushRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');

        $this->clock = Mockery::mock('MCP\DataType\Time\Clock');
        $this->clock
            ->shouldReceive('read')
            ->andReturn(new TimePoint(2014, 3, 15, 12, 0, 0, 'UTC'))
            ->byDefault();
    }

    public function testWithBadDate()
    {
        $this->request = new Request(Environment::mock([
            'QUERY_STRING' => 'since=derp'
        ]));

        $controller = new QueueController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo,
            $this->clock
        );

        $controller($this->request, $this->response);
        $this->assertSame(400, $this->response->getStatus());
    }

    public function testWithGoodDate()
    {
        $this->request = new Request(Environment::mock([
            'QUERY_STRING' => 'since=2014-03-15T12:00:00-0500'
        ]));

        $builds = $pushes = [];

        $this->buildRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($builds));

        $this->pushRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($pushes));

        $controller = new QueueController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo,
            $this->clock
        );

        $controller($this->request, $this->response);
        $this->assertSame(404, $this->response->getStatus());
    }

    public function testWithNoResults()
    {
        $builds = $pushes = [];

        $this->buildRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($builds));

        $this->pushRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($pushes));

        $controller = new QueueController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo,
            $this->clock
        );

        $controller($this->request, $this->response);
        $this->assertSame(404, $this->response->getStatus());
    }

    public function testWithJobs()
    {
        $builds = [
            new Build
        ];

        $pushes = [
            new Push
        ];

        $this->buildNormalizer
            ->shouldReceive('normalize')
            ->with($builds[0], Mockery::type('array'))
            ->andReturn(['id' => '1']);

        $this->pushNormalizer
            ->shouldReceive('normalize')
            ->with($pushes[0], Mockery::type('array'))
            ->andReturn(['id' => '1']);

        $this->buildRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($builds));

        $this->pushRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($pushes));

        $this->api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($links), $this->storeExpectation($content));

        $controller = new QueueController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo,
            $this->clock
        );

        $controller($this->request, $this->response);

        $expectedContent = [
            [
                'uniqueId' => 'push-1',
                'type' => 'push',
                'id' => '1'
            ],
            [
                'uniqueId' => 'build-1',
                'type' => 'build',
                'id' => '1'
            ]
        ];

        $this->assertArrayHasKey('self', $links);
        $this->assertSame($expectedContent, $content);
    }

    public function storeExpectation(&$stored)
    {
        return Mockery::on(function($v) use (&$stored) {
            $stored = $v;
            return true;
        });
    }
}
