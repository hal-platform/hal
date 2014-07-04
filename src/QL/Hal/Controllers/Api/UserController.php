<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API User Controller
 */
class UserController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param UserRepository $users
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        UserRepository $users
    ) {
        $this->api = $api;
        $this->url = $url;
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
        $user = $this->users->findOneBy(['id' => $params['id']]);

        if (!($user instanceof User)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.user', ['id' => $user->getId()]], 'type' => 'User'],
            'users' => ['href' => 'api.users', 'type' => 'Users']
        ];

        $content = [
            'id' => $user->getId(),
            'url' => $this->url->urlFor('user', ['id' => $user->getId()]),
            'handle' => $user->getHandle(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'picture' => $user->getPictureUrl()->asString()
        ];

        $this->api->prepareResponse($response, $links, $content);
    }
}
