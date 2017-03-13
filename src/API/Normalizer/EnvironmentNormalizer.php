<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\NormalizerInterface;
use QL\Hal\Core\Entity\Environment;

class EnvironmentNormalizer implements NormalizerInterface
{
    /**
     * @param Environment $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Environment $environment
     *
     * @return Hyperlink|null
     */
    public function link($environment): ?Hyperlink
    {
        if (!$environment instanceof Environment) {
            return null;
        }

        return new Hyperlink(
            ['api.environment', ['environment' => $environment->id()]],
            $environment->name()
        );
    }

    /**
     * @param Environment|null $environment
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($environment, array $embed = []): ?HypermediaResource
    {
        if (!$environment instanceof Environment) {
            return null;
        }

        $data = [
            'id' => $environment->id(),
            'name' => $environment->name(),
            'is_production' => $environment->isProduction()
        ];

        $links = [
            'self' => $this->link($environment)
        ];

        $resource = new HypermediaResource($data, $links);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
