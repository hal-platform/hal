<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\UserRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Users Controller
 */
class UsersController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @param ApiHelper $api
     * @param UserRepository $users
     */
    public function __construct(
        ApiHelper $api,
        UserRepository $users
    ) {
        $this->api = $api;
        $this->users = $users;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $links = [
            'self' => ['href' => 'api.users', 'type' => 'Users'],
            'index' => ['href' => 'api.index']
        ];

        $users = $this->users->findBy([], ['id' => 'ASC']);

        $content = [
            'count' => count($users),
            'users' => []
        ];

        foreach ($users as $user) {
            $content['users'][] = [
                'id' => $user->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.user', ['id' => $user->getId()]], 'type' => 'User']
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
