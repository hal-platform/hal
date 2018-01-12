<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\Job\JobEvent;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;

class EventNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param JobEvent $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param JobEvent|null $event
     *
     * @return Hyperlink|null
     */
    public function link($event): ?Hyperlink
    {
        if (!$event instanceof JobEvent) {
            return null;
        }

        $title = sprintf('[%s] %s', $event->order(), $event->message());

        return new Hyperlink(
            ['api.event', ['event' => $event->id()]],
            $title
        );
    }

    /**
     * @param JobEvent|null $event
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($event, array $embed = []): ?HypermediaResource
    {
        if (!$event instanceof JobEvent) {
            return null;
        }

        $data = [
            'id' => $event->id(),
            'name' => $event->stage(),
            'order' => $event->order(),
            'message' => $event->message(),
            'status' => $event->status(),
            'created' => $event->created(),
            'data' => '**DATA**'
        ];

        if (in_array('data', $embed)) {
            $data['data'] = $event->parameters();
        }

        $links = [
            'self' => $this->link($event)
        ];

        $resource = new HypermediaResource($data, $links, [
            'job' => $event->job()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
