<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;
use QL\Hal\Core\Entity\Push;

class PushNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param Push|null $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Push|null $push
     *
     * @return Hyperlink|null
     */
    public function link($push): ?Hyperlink
    {
        if (!$push instanceof Push) {
            return null;
        }

        return new Hyperlink(
            ['api.push', ['push' => $push->id()]],
            $push->id()
        );
    }

    /**
     * @param Push|null $push
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($push, array $embed = []): ?HypermediaResource
    {
        if (!$push instanceof Push) {
            return null;
        }

        $data = [
            'id' => $push->id(),
            'status' => $push->status(),

            'created' => $push->created(),
            'start' => $push->start(),
            'end' => $push->end()
        ];

        $links = [
            'self' => $this->link($push),
            'events' => new Hyperlink(['api.push.events', ['push' => $push->id()]]),
            'page' => new Hyperlink(
                ['push', ['push' => $push->id()]],
                '',
                'text/html'
            )
        ];

        $resource = new HypermediaResource($data, $links, [
            'user' => $push->user(),
            'build' => $push->build(),
            'target' => $push->deployment(),
            'application' => $push->application()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
