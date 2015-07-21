<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\EventLog;

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
     * @param EventLog|null $log
     *
     * @return array|null
     */
    public function link(EventLog $log = null)
    {
        if (!$log) return $log;

        return [
            'href' => ['api.event.log', ['id' => $log->id()]],
            'title' => $log->id()
        ];
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

        $data = [
            'id' => $log->id(),
            'event' => $log->event(),
            'order' => $log->order(),
            'message' => $log->message(),
            'status' => $log->status(),
            'created' => $log->created(),
            'data' => '**DATA**'
        ];

        if (in_array('data', $embed)) {
            $data['data'] = $log->data();
        }

        $embedded = $this->resolveEmbedded($properties, array_merge($this->embed, $embed));
        $links = ['self' => $this->link($log)] + $links;

        return $this->buildResource($data, $embedded, $links);
    }
}
