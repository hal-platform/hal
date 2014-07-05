<?php

namespace QL\Hal\Controllers\Api\Group;

use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Group Controller
 */
class GroupController
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
     * @var GroupRepository
     */
    private $groups;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param GroupRepository $groups
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        GroupRepository $groups
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->groups = $groups;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $group = $this->groups->findOneBy(['id' => $params['id']]);

        if (!($group instanceof Group)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.group', ['id' => $group->getId()]], 'type' => 'Group'],
            'groups' => ['href' => 'api.groups', 'type' => 'Groups'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $group->getId(),
            'url' => $this->url->urlFor('group', ['id' => $group->getId()]),
            'key' => $group->getKey(),
            'name' => $group->getName(),
            'repositories' => []
        ];

        foreach ($group->getRepositories() as $repository) {
            $content['repositories'][] = [
                'id' => $repository->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository']
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
