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
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Utility\SortingTrait;

class ServerNormalizer implements ResourceNormalizerInterface
{
    use SortingTrait;

    /**
     * @param Server $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        if (!$input instanceof Server) {
            return null;
        }

        return $this->resource($input);
    }

    /**
     * @param Server|null $server
     *
     * @return Hyperlink|null
     */
    public function link($server): ?Hyperlink
    {
        if (!$server instanceof Server) {
            return null;
        }

        return new Hyperlink(
            ['api.server', ['server' => $server->id()]],
            $server->formatPretty()
        );
    }

    /**
     * @param Server|null $server
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($server, array $embed = ['environment']): ?HypermediaResource
    {
        if (!$server instanceof Server) {
            return null;
        }

        $data = [
            'id' => $server->id(),
            'type' => $server->type(),
            'name' => $server->name()
        ];

        $links = [
            'self' => $this->link($server)
        ];

        $resource = new HypermediaResource($data, $links, [
            'environment' => $server->environment(),
            'deployments' => $server->deployments()->toArray()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
