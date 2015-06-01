<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
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
    public function link(EventLog $log = null)
    {
        return  (is_null($log)) ? null :$this->buildLink(
            ['api.event.log', ['id' => $log->id()]],
            [
                'title' => $log->id()
            ]
        );
    }

    /**
     * @param EventLog $log
     * @param array $embed
     * @return array
     */
    public function resource(EventLog $log = null, array $embed = [])
    {
        if (is_null($log)) {
            return null;
        }

        $properties = [
            'build' => $log->build(),
            'push' => $log->push()
        ];

        $links = [];

        if ($log->build()) {
            $links['build'] = $this->builds->link($log->build());
        }

        if ($log->push()) {
            $links['push'] = $this->pushes->link($log->push());
        }

        return $this->buildResource(
            [
                'id' => $log->id(),
                'event' => $log->event(),
                'order' => $log->order(),
                'message' => $log->message(),
                'status' => $log->status(),
                'created' => $log->created(),
                'data' => $log->data()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($log)
            ] + $links
        );
    }
}
