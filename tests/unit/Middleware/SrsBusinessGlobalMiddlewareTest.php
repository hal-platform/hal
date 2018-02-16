<?php

namespace Hal\UI\Middleware;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use QL\Panthor\HTTP\CookieHandler;

class SrsBusinessGlobalMiddlewareTest extends MockeryTestCase
{
    public $cookies;

    public function setUp()
    {
        $this->cookies = Mockery::mock(CookieHandler::class);
    }

    public function testAttributeIsSetIfCookieMissing()
    {
        $request = new ServerRequest('GET', '/path');
        $response = new Response(200);

        $actualReq = null;
        $next = function($req, $res) use(&$actualReq) { $actualReq = $req; return $res; };

        $this->cookies
            ->shouldReceive('getCookie')
            ->with($request, 'seriousbusiness')
            ->andReturnNull();

        $middleware = new SrsBusinessGlobalMiddleware($this->cookies);
        $res = $middleware($request, $response, $next);

        $attributeValue = $actualReq
            ->getAttribute('is_serious_business_mode');

        $templateValue = $actualReq
            ->getAttribute('template_context')
            ->get('is_serious_business_mode');

        $this->assertSame(false, $attributeValue);
        $this->assertSame(false, $templateValue);
    }

    public function testAttributeIsSetCorrectlyIfCookieFound()
    {
        $request = new ServerRequest('GET', '/path');
        $response = new Response(200);

        $actualReq = null;
        $next = function($req, $res) use(&$actualReq) { $actualReq = $req; return $res; };

        $this->cookies
            ->shouldReceive('getCookie')
            ->with($request, 'seriousbusiness')
            ->andReturn('1');

        $middleware = new SrsBusinessGlobalMiddleware($this->cookies);
        $res = $middleware($request, $response, $next);

        $attributeValue = $actualReq
            ->getAttribute('is_serious_business_mode');

        $templateValue = $actualReq
            ->getAttribute('template_context')
            ->get('is_serious_business_mode');

        $this->assertSame(true, $attributeValue);
        $this->assertSame(true, $templateValue);
    }

}
