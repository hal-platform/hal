<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;

class EventLogNormalizer
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type TimeHelper
     */
    private $time;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @param ApiHelper $api
     * @param TimeHelper $time
     * @param UrlHelper $url
     */
    public function __construct(ApiHelper $api, TimeHelper $time, UrlHelper $url)
    {
        $this->api = $api;
        $this->time = $time;
        $this->url = $url;
    }

    /**
     * Normalize to the linked resource.
     *
     * @param EventLog $log
     * @return array
     */
    public function linked(EventLog $log)
    {
        return $this->api->parseLink([
            'href' => ['api.event.log', ['id' => $log->getId()]],
            'title' => $log->getId()
        ]);
    }

    /**
     * Normalize to the full entity properties.
     *
     * @param EventLog $log
     * @return array
     */
    public function normalize(EventLog $log)
    {
        $url = null;

        if ($log->getBuild()) {
            $url = $this->url->urlFor('build', ['build' => $log->getBuild()->getId()]);

        } else if ($log->getPush()) {
            $url = $this->url->urlFor('push', ['push' => $log->getPush()->getId()]);
        }

        $content = [
            'id' => $log->getId(),
            'url' => $url,
            'event' => $log->getEvent(),
            'order' => $log->getOrder(),
            'message' => $log->getMessage(),
            'status' => $log->getStatus(),
            'created' => [
                'text' => $this->time->relative($log->getCreated(), false),
                'datetime' => $this->time->format($log->getCreated(), false, 'c')
            ],
            'data' => $log->getData()
        ];

        return array_merge_recursive(
            $content,
            $this->links($log)
        );
    }

    /**
     * @param EventLog $log
     * @return array
     */
    private function links(EventLog $log)
    {
        $links = [
            '_links' => [
                'self' => $this->linked($log)
            ]
        ];

        if ($log->getBuild()) {
            $buildId = $log->getBuild()->getId();
            $links['_links']['build'] = $this->api->parseLink(['href' => ['api.build', ['id' => $buildId]]]);
            $links['_links']['index'] = $this->api->parseLink(['href' => ['api.build.logs', ['id' => $buildId]]]);
        }

        if ($log->getPush()) {
            $pushId = $log->getPush()->getId();
            $links['_links']['push'] = $this->api->parseLink(['href' => ['api.push', ['id' => $pushId]]]);
            $links['_links']['index'] = $this->api->parseLink(['href' => ['api.push.logs', ['id' => $pushId]]]);
        }

        return $links;
    }
}
