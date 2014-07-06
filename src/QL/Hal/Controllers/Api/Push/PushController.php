<?php

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\Consumer;

/**
 * API Push Controller
 */
class PushController
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
     * @var TimeHelper
     */
    private $time;

    /**
     * @var PushRepository
     */
    private $pushes;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param TimeHelper $time
     * @param PushRepository $pushes
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        TimeHelper $time,
        PushRepository $pushes
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->time = $time;
        $this->pushes = $pushes;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $push = $this->pushes->findOneBy(['id' => $params['id']]);

        if (!($push instanceof Push)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.push', ['id' => $push->getId()]], 'type' => 'Push'],
            'log' => ['href' => ['api.push.log', ['id' => $push->getId()]], 'type' => 'Push Log'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $push->getId(),
            'url' => $this->url->urlFor('push', ['id' => $push->getId()]),
            'status' => $push->getStatus(),
            'start' => [
                'text' => $this->time->relative($push->getStart(), false),
                'datetime' => $this->time->format($push->getStart(), false, 'c')
            ],
            'end' => [
                'text' => $this->time->relative($push->getEnd(), false),
                'datetime' => $this->time->format($push->getEnd(), false, 'c')
            ],
            'build' => [
                'id' => $push->getBuild()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.build', ['id' => $push->getBuild()->getId()]], 'type' => 'Build']
                ])
            ],
            'deployment' => [
                'id' => $push->getDeployment()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.deployment', ['id' => $push->getDeployment()->getId()]], 'type' => 'Deployment']
                ])
            ],
            'initiator' => []
        ];

        if ($push->getUser() instanceof User) {
            $content['initiator']['user'] = [
                'id' => $push->getUser()->getId(),
                '_links' => $this->api->parseLinks([
                            'self' => ['href' => ['api.user', ['id' => $push->getUser()->getId()]], 'type' => 'User']
                        ])
            ];
        } else {
            $content['initiator']['user'] = null;
        }
        if ($push->getConsumer() instanceof Consumer) {
            $content['initiator']['consumer'] = [
                'id' => $push->getConsumer()->getId()
            ];
        } else {
            $content['initiator']['consumer'] = null;
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
