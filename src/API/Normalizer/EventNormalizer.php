<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\NormalizerInterface;
use Hal\UI\Api\Utility\EmbeddedResolutionTrait;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\EventLog;

class EventNormalizer implements NormalizerInterface
{
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
    public function __construct(BuildNormalizer $builds, PushNormalizer $pushes)
    {
        $this->builds = $builds;
        $this->pushes = $pushes;

        $this->embed = [];
    }

    /**
     * @param EventLog $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param EventLog|null $log
     *
     * @return Hyperlink|null
     */
    public function link(EventLog $log = null)
    {
        if (!$log) {
            return $log;
        }

        $title = sprintf('[%s] %s', $log->order(), $log->message());

        return new Hyperlink(
            ['api.event', ['event' => $log->id()]],
            $title
        );
    }

    /**
     * @param EventLog $log
     * @param array $embed
     *
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

        return $this->buildResource($data, $embedded, $this->buildLinks($log));
    }

    /**
     * @param EventLog $log
     *
     * @return array
     */
    private function buildLinks(EventLog $log)
    {
        $links = [
            'self' => $this->link($log)
        ];

        if ($log->build()) {
            $links['build'] = $this->builds->link($log->build());
        }

        if ($log->push()) {
            $links['push'] = $this->pushes->link($log->push());
        }

        return $links;
    }
}
