<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\Common\Collections\ArrayCollection;
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

        $this->twig = Mockery::mock('Twig_Template');
        $this->layout = Mockery::mock('QL\Hal\Layout');
        $this->buildRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\BuildRepository');
        $this->pushRepo = Mockery::mock('QL\Hal\Core\Entity\Repository\PushRepository');
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

        $context = null;
        $this->layout
            ->shouldReceive('render')
            ->with($this->twig, Mockery::on(function($v) use (&$context) {
                $context = $v;
                return true;
            }))
            ->once();

        $controller = new QueueController(
            $this->twig,
            $this->layout,
            $this->buildRepo,
            $this->pushRepo
        );

        $controller($this->request, $this->response);

        $this->assertSame(['jobs' => []], $context);
    }

    public function testWithJobs()
    {
        $builds = [
            new Build
        ];

        $pushes = [
            new Push,
            new Push
        ];

        $expectedContext = ['jobs' => [
            $builds[0],
            $pushes[0],
            $pushes[1]
        ]];

        $this->buildRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($builds));

        $this->pushRepo
            ->shouldReceive('matching')
            ->andReturn(new ArrayCollection($pushes));

        $context = null;
        $this->layout
            ->shouldReceive('render')
            ->with($this->twig, Mockery::on(function($v) use (&$context) {
                $context = $v;
                return true;
            }))
            ->once();

        $controller = new QueueController(
            $this->twig,
            $this->layout,
            $this->buildRepo,
            $this->pushRepo
        );

        $controller($this->request, $this->response);

        $this->assertSame($expectedContext, $context);
    }
}
