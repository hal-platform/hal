<?php

namespace Hal\UI\Controllers\Admin\IDP;

use Doctrine\ORM\EntityManagerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\UI\Middleware\CSRFMiddleware;
use Hal\UI\Middleware\FlashGlobalMiddleware;
use Hal\UI\Flash;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Slim\Route;

class RemoveIdentityProviderHandlerTest extends MockeryTestCase
{
    public $em;
    public $uri;

    public function setUp()
    {
        $this->em = Mockery::mock(EntityManagerInterface::class);
        $this->uri = Mockery::mock(URI::class);
    }

    public function testSuccess()
    {
        $idp = (new UserIdentityProvider())
            ->withName('testIdp');

        $flash = new Flash;

        $response = new Response(200);
        $route = (new Route('POST', '/path', null));
        $request = (new ServerRequest('POST', '/path'))
            ->withAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE, $flash)
            ->withAttribute(CSRFMiddleware::CSRF_ERROR_ATTRIBUTE, false)
            ->withAttribute(UserIdentityProvider::class, $idp)
            ->withAttribute('route', $route);

        $this->em
            ->shouldReceive('getRepository->findOneBy')
            ->with(['provider' => $idp])
            ->andReturn(null);

        $this->em
            ->shouldReceive('remove')
            ->with($idp);
        $this->em
            ->shouldReceive('flush');

        $this->uri
            ->shouldReceive('uriFor')
            ->andReturn('');

        $controller = new RemoveIdentityProviderHandler($this->em, $this->uri);
        $res = $controller($request, $response);

        $flash = $request
            ->getAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE)
            ->getMessages();

        $this->assertSame(302, $res->getStatusCode());
        $this->assertCount(1, $flash);
        $this->assertSame(Flash::SUCCESS, $flash[0]['type']);
        $this->assertSame('"testIdp" identity provider removed.', $flash[0]['message']);
    }

    public function testFailedCSRF()
    {
        $idp = new UserIdentityProvider();

        $flash = new Flash;

        $response = new Response(200);
        $route = (new Route('POST', '/path', null));
        $request = (new ServerRequest('POST', '/path'))
            ->withAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE, $flash)
            ->withAttribute(CSRFMiddleware::CSRF_ERROR_ATTRIBUTE, true)
            ->withAttribute(UserIdentityProvider::class, $idp)
            ->withAttribute('route', $route);

        $this->em
            ->shouldReceive('getRepository->findOneBy')
            ->with(['provider' => $idp])
            ->andReturn([]);

        $this->uri
            ->shouldReceive('uriFor')
            ->andReturn('');

        $controller = new RemoveIdentityProviderHandler($this->em, $this->uri);
        $res = $controller($request, $response);

        $flash = $request
            ->getAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE)
            ->getMessages();

        $this->assertSame(302, $res->getStatusCode());
        $this->assertCount(1, $flash);
        $this->assertSame(Flash::ERROR, $flash[0]['type']);
        $this->assertSame('CSRF validation failed. Please try again.', $flash[0]['message']);
    }

    public function testCanNotRemoveIDP()
    {
        $idp = (new UserIdentityProvider())
            ->withName('testIdp');

        $flash = new Flash;

        $response = new Response(200);
        $route = (new Route('POST', '/path', null));
        $request = (new ServerRequest('POST', '/path'))
            ->withAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE, $flash)
            ->withAttribute(CSRFMiddleware::CSRF_ERROR_ATTRIBUTE, false)
            ->withAttribute(UserIdentityProvider::class, $idp)
            ->withAttribute('route', $route);

        $this->em
            ->shouldReceive('getRepository->findOneBy')
            ->with(['provider' => $idp])
            ->andReturn([]);

        $this->uri
            ->shouldReceive('uriFor')
            ->andReturn('');

        $controller = new RemoveIdentityProviderHandler($this->em, $this->uri);
        $res = $controller($request, $response);

        $flash = $request
            ->getAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE)
            ->getMessages();

        $this->assertSame(302, $res->getStatusCode());
        $this->assertCount(1, $flash);
        $this->assertSame(Flash::ERROR, $flash[0]['type']);
        $this->assertSame('"testIdp" identity provider cannot be removed.', $flash[0]['message']);
    }
}
