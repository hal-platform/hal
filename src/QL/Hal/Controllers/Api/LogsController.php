<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\LogRepository;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

class LogsController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var TimeHelper
     */
    private $time;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var LogRepository
     */
    private $logs;

    /**
     * @param ApiHelper $api
     * @param TimeHelper $time
     * @param UrlHelper $url
     * @param LogRepository $logs
     */
    public function __construct(
        ApiHelper $api,
        TimeHelper $time,
        UrlHelper $url,
        LogRepository $logs
    ) {
        $this->api = $api;
        $this->time = $time;
        $this->url = $url;
        $this->logs = $logs;
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
            'self' => ['href' => 'api.logs']
        ];

        $content = [];

        foreach ($this->logs->findBy([], ['recorded' => 'DESC']) as $log) {
            $content[] = [
                'id' => $log->getId(),
                'date' => $this->time->format($log->getRecorded(), false, 'c'),
                'user' => $this->url->urlFor('api.user', ['id' => $log->getUser()->getId()]),
                'entity' => $log->getEntity(),
                'action' => $log->getAction(),
                'changeset' => $log->getData()
            ];
        }

        if (false) {
            call_user_func($notFound);
            return;
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
