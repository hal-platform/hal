<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\User;
use Slim\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersControllerTest extends PHPUnit_Framework_TestCase
{
    public $request;
    public $response;

    public function setUp()
    {
        $this->request = new Request(Environment::mock());
        $this->response = new Response;
    }

    public function testNoUserFound()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\UserRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\UserNormalizer');

        $repo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturnNull();

        $controller = new UsersController(
            $api,
            $repo,
            $normalizer
        );

        $controller($this->request, $this->response, ['id' => 'test']);

        $this->assertSame(404, $this->response->getStatus());
    }

    public function testUserFoundAndNormalized()
    {
        $api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
        $repo = Mockery::mock('QL\Hal\Core\Entity\Repository\UserRepository');
        $normalizer = Mockery::mock('QL\Hal\Api\UserNormalizer');

        $users = [
            new User,
            new User
        ];

        $repo
            ->shouldReceive('findBy')
            ->with([], Mockery::type('array'))
            ->andReturn($users);

        $normalizer
            ->shouldReceive('normalize')
            ->with($users[0])
            ->andReturn('normalized-user1');
        $normalizer
            ->shouldReceive('normalize')
            ->with($users[1])
            ->andReturn('normalized-user2');

        $api
            ->shouldReceive('prepareResponse')
            ->with($this->response, ['normalized-user1','normalized-user2'])
            ->once();

        $controller = new UsersController(
            $api,
            $repo,
            $normalizer
        );

        $controller($this->request, $this->response);

        $this->assertSame(200, $this->response->getStatus());
    }
}
