<?php

namespace Hal\UI\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Hal\Core\Entity\Application;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Slim\Route;

class RequireEntityMiddlewareTest extends MockeryTestCase
{
    public $em;

    public $next;
    public $actualReq;

    public function setUp()
    {
        $this->em = Mockery::mock(EntityManagerInterface::class);

        $this->actualReq = null;
        $this->next = function($req, $res) { $this->actualReq = $req; return $res; };
    }

    public function testInvalidEntityDoesNothing()
    {
        $response = new Response(200);
        $route = (new Route('GET', '/path', null))
            ->setArgument('unknown', 1234);
        $request = (new ServerRequest('GET', '/path'))
            ->withAttribute('route', $route);

        $middleware = new RequireEntityMiddleware($this->em, function() {});
        $res = $middleware($request, $response, $this->next);

        $attributes = $this->actualReq->getAttributes();

        $this->assertCount(1, $attributes);
    }

    public function testInvalidIDFormatReturnsNotFound()
    {
        $response = new Response(200);
        $route = (new Route('GET', '/path', null))
            ->setArgument('application', '1234');
        $request = (new ServerRequest('GET', '/path'))
            ->withAttribute('route', $route);

        $actual = false;
        $notFound = function() use (&$actual) { $actual = true; };

        $middleware = new RequireEntityMiddleware($this->em, $notFound);
        $res = $middleware($request, $response, $this->next);

        $this->assertSame(true, $actual);
    }

    public function testEntityParamIsFoundButNoEntityLoadedFromDB()
    {
        $response = new Response(200);
        $route = (new Route('GET', '/path', null))
            ->setArgument('application', '97837086ce8241d5bec03826968f5c46');
        $request = (new ServerRequest('GET', '/path'))
            ->withAttribute('route', $route);

        $actual = false;
        $notFound = function() use (&$actual) { $actual = true; };

        $this->em
            ->shouldReceive('getRepository->find')
            ->with('97837086ce8241d5bec03826968f5c46')
            ->andReturnNull();

        $middleware = new RequireEntityMiddleware($this->em, $notFound);
        $res = $middleware($request, $response, $this->next);

        $this->assertSame(true, $actual);
    }

    public function testEntityIsFoundAndAttachedToRequest()
    {
        $response = new Response(200);
        $route = (new Route('GET', '/path', null))
            ->setArgument('application', '97837086ce8241d5bec03826968f5c46');
        $request = (new ServerRequest('GET', '/path'))
            ->withAttribute('route', $route);

        $application = new Application;

        $this->em
            ->shouldReceive('getRepository->find')
            ->with('97837086ce8241d5bec03826968f5c46')
            ->andReturn($application);

        $middleware = new RequireEntityMiddleware($this->em, function() {});
        $res = $middleware($request, $response, $this->next);

        $attributes = $this->actualReq->getAttributes();

        $this->assertCount(2, $attributes);

        $this->assertSame($application, $attributes[Application::class]);
    }
}
