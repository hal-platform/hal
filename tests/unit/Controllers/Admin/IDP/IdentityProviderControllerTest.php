<?php

namespace Hal\UI\Controllers\Admin\IDP;

use Doctrine\ORM\EntityManagerInterface;
use QL\Panthor\TemplateInterface;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Hal\Core\Entity\System\UserIdentityProvider;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Slim\Route;

class IdentityProviderControllerTest extends MockeryTestCase
{
    public $em;
    public $template;

    public function setUp()
    {
        $this->em = Mockery::mock(EntityManagerInterface::class);
        $this->template = Mockery::mock(TemplateInterface::class);
    }

    public function testCanRemoveIDP()
    {
        $idp = new UserIdentityProvider();

        $response = new Response(200);
        $route = (new Route('POST', '/path', null));
        $request = (new ServerRequest('POST', '/path'))
            ->withAttribute(UserIdentityProvider::class, $idp)
            ->withAttribute('route', $route);

        $this->em
            ->shouldReceive('getRepository->findOneBy')
            ->with(['provider' => $idp])
            ->andReturn(null);

        $this->template
            ->shouldReceive('render')
            ->with([
                'idp' => $idp,
                'can_remove' => true
            ])
            ->andReturn('');

        $controller = new IdentityProviderController($this->template, $this->em);
        $res = $controller($request, $response);

        $this->assertSame(200, $res->getStatusCode());
    }

    public function testCanNotRemoveIDP()
    {
        $idp = new UserIdentityProvider();

        $response = new Response(200);
        $route = (new Route('POST', '/path', null));
        $request = (new ServerRequest('POST', '/path'))
            ->withAttribute(UserIdentityProvider::class, $idp)
            ->withAttribute('route', $route);

        $this->em
            ->shouldReceive('getRepository->findOneBy')
            ->with(['provider' => $idp])
            ->andReturn([]);

        $this->template
            ->shouldReceive('render')
            ->with([
                'idp' => $idp,
                'can_remove' => false
            ])
            ->andReturn('');

        $controller = new IdentityProviderController($this->template, $this->em);
        $res = $controller($request, $response);

        $this->assertSame(200, $res->getStatusCode());
    }
}
