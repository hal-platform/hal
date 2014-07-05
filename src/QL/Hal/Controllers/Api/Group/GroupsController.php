<?php

namespace QL\Hal\Controllers\Api\Group;

use QL\Hal\Core\Entity\Repository\GroupRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Groups Controller
 */
class GroupsController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var GroupRepository
     */
    private $groups;

    /**
     * @param ApiHelper $api
     * @param GroupRepository $groups
     */
    public function __construct(
        ApiHelper $api,
        GroupRepository $groups
    ) {
        $this->api = $api;
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
        $links = [
            'self' => ['href' => 'api.groups', 'type' => 'Groups'],
            'index' => ['href' => 'api.index']
        ];

        $groups = $this->groups->findBy([], ['id' => 'ASC']);

        $content = [
            'count' => count($groups),
            'groups' => []
        ];

        foreach ($groups as $group) {
            $content['groups'][] = [
                'id' => $group->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.group', ['id' => $group->getId()]], 'type' => 'Group']
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
