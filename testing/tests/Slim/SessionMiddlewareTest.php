<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Slim;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Session;
use QL\Hal\SessionHandler;
use Slim\Middleware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SessionMiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testMiddlewareDoesNothingIfNotLoaded()
    {
        $handler = Mockery::mock(SessionHandler::class);
        $di = Mockery::mock(ContainerInterface::class);
        $next = Mockery::mock(Middleware::class);

        $next
            ->shouldReceive('call')
            ->once();

        $handler
            ->shouldReceive('isLoaded')
            ->andReturn(false);

        $di
            ->shouldReceive('get')
            ->never();

        $middleware = new SessionMiddleware($handler, $di);
        $middleware->setNextMiddleware($next);

        $middleware->call();
    }

    public function testMiddlewareDoesNotSavesSessionIfInvalid()
    {
        $handler = Mockery::mock(SessionHandler::class);
        $di = Mockery::mock(ContainerInterface::class);
        $next = Mockery::mock(Middleware::class);

        $next
            ->shouldReceive('call');

        $handler
            ->shouldReceive('isLoaded')
            ->andReturn(true);

        $di
            ->shouldReceive('get')
            ->with('test_session')
            ->andReturn('derpherp')
            ->once();

        $handler
            ->shouldReceive('save')
            ->never();

        $middleware = new SessionMiddleware($handler, $di, 'test_session');
        $middleware->setNextMiddleware($next);

        $middleware->call();
    }

    public function testMiddlewareSavesSession()
    {
        $handler = Mockery::mock(SessionHandler::class);
        $di = Mockery::mock(ContainerInterface::class);
        $next = Mockery::mock(Middleware::class);
        $session = Mockery::mock(Session::class);

        $next
            ->shouldReceive('call');

        $handler
            ->shouldReceive('isLoaded')
            ->andReturn(true);

        $di
            ->shouldReceive('get')
            ->with('test_session')
            ->andReturn($session);

        $handler
            ->shouldReceive('save')
            ->with($session)
            ->once();

        $middleware = new SessionMiddleware($handler, $di, 'test_session');
        $middleware->setNextMiddleware($next);

        $middleware->call();
    }
}
