<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\HypermediaResource;
use Hal\UI\Api\ResourceNormalizerInterface;
use QL\Hal\Core\Entity\EventLog;

class EventNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param EventLog $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param EventLog|null $event
     *
     * @return Hyperlink|null
     */
    public function link($event): ?Hyperlink
    {
        if (!$event instanceof EventLog) {
            return null;
        }

        $title = sprintf('[%s] %s', $event->order(), $event->message());

        return new Hyperlink(
            ['api.event', ['event' => $event->id()]],
            $title
        );
    }

    /**
     * @param EventLog|null $event
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($event, array $embed = []): ?HypermediaResource
    {
        if (!$event instanceof EventLog) {
            return null;
        }

        $data = [
            'id' => $event->id(),
            'name' => $event->event(),
            'order' => $event->order(),
            'message' => $event->message(),
            'status' => $event->status(),
            'created' => $event->created(),
            'data' => '**DATA**'
        ];

        if (in_array('data', $embed)) {
            $data['data'] = $event->data();
        }

        $links = [
            'self' => $this->link($event)
        ];

        $resource = new HypermediaResource($data, $links, [
            'build' => $event->build(),
            'push' => $event->push()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
