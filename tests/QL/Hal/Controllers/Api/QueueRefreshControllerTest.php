<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class QueueRefreshControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public $api;
    public $buildNormalizer;
    public $pushNormalizer;
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
    }

    public function testScenarioThatShouldNeverHappen()
    {
        $controller = new QueueRefreshController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo
        );

        $controller($this->request, $this->response, []);

        $this->assertSame(400, $this->response->getStatus());
    }

    public function testUpdatingInvalidJobsDoesNotHitDatabase()
    {
        $controller = new QueueRefreshController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo
        );

        $controller($this->request, $this->response, ['uniqueId' => '1 2abc abc4 5-build buildpush-5']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testUpdatingSingleJob()
    {
        $pushes = [new Push];

        $this->pushRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($pushes));

        $this->pushNormalizer
            ->shouldReceive('normalize')
            ->with($pushes[0])
            ->andReturn(['id' => '5']);

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
        $this->api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content));

        $controller = new QueueRefreshController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo
        );

        $controller($this->request, $this->response, ['uniqueId' => 'push-5']);

        $expectedContent = [
            'count' => 1,
            '_links' => [
                'self' => 'link'
            ],
            '_embedded' => [
                'jobs' => [
                    [
                        'uniqueId' => 'push-5',
                        'type' => 'push',
                        'id' => '5'
                    ]
                ]
            ]
        ];

        $this->assertSame($expectedContent, $content);
    }

    public function testUpdatingMultipleJobs()
    {
        $builds = [new Build];
        $pushes = [new Push];

        $this->buildRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($builds));

        $this->pushRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($pushes));

        $this->buildNormalizer
            ->shouldReceive('normalize')
            ->with($builds[0])
            ->andReturn(['id' => 'abc']);

        $this->pushNormalizer
            ->shouldReceive('normalize')
            ->with($pushes[0])
            ->andReturn(['id' => '5']);

        $this->api
            ->shouldReceive('parseLink')
            ->andReturn('link');
        $this->api
            ->shouldReceive('prepareResponse')
            ->with($this->response, $this->storeExpectation($content));

        $controller = new QueueRefreshController(
            $this->api,
            $this->buildNormalizer,
            $this->pushNormalizer,
            $this->buildRepo,
            $this->pushRepo
        );

        $controller($this->request, $this->response, ['uniqueId' => 'push-5 build-abc']);

        $expectedContent = [
            'count' => 2,
            '_links' => [
                'self' => 'link'
            ],
            '_embedded' => [
                'jobs' => [
                    [
                        'uniqueId' => 'build-abc',
                        'type' => 'build',
                        'id' => 'abc'
                    ],
                    [
                        'uniqueId' => 'push-5',
                        'type' => 'push',
                        'id' => '5'
                    ]
                ]
            ]
        ];

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
