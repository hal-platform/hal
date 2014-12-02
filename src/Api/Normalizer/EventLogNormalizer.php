<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\EventLog;

/**
 *
 */
class EventLogNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var BuildNormalizer
     */
    private $builds;

    /**
     * @var PushNormalizer
     */
    private $pushes;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param BuildNormalizer $builds
     * @param PushNormalizer $pushes
     */
    public function __construct(
        BuildNormalizer $builds,
        PushNormalizer $pushes
    ) {
        $this->builds = $builds;
        $this->pushes = $pushes;

        $this->embed = [];
    }

    /**
     * @param EventLog $log
     * @return array
     */
    public function link(EventLog $log)
    {
        return $this->buildLink(
            ['api.event.log', ['id' => $log->getId()]],
            [
                'title' => $log->getId()
            ]
        );
    }

    /**
     * @param EventLog $log
     * @param array $embed
     * @return array
     */
    public function resource(EventLog $log, array $embed = [])
    {
        $properties = [
            'build' => $log->getBuild(),
            'push' => $log->getPush()
        ];

        $links = [];

        if ($log->getBuild()) {
            $links['build'] = $this->builds->link($log->getBuild());
        }

        if ($log->getPush()) {
            $links['push'] = $this->pushes->link($log->getPush());
        }

        return $this->buildResource(
            [
                'id' => $log->getId(),
                'event' => $log->getEvent(),
                'order' => $log->getOrder(),
                'message' => $log->getMessage(),
                'status' => $log->getStatus(),
                'created' => $log->getCreated(),
                'data' => $log->getData()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($log)
            ] + $links
        );
    }
}