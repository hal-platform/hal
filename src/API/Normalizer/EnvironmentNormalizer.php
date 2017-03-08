<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\NormalizerInterface;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Environment;

class EnvironmentNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;

    /**
     * @param Environment $input
     *
     * @return array|null
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
    public function link(Environment $environment = null): ?Hyperlink
    {
        if (!$environment) {
            return null;
        }

        return new Hyperlink(
            ['api.environment', ['environment' => $environment->id()]],
            $environment->name()
        );
    }

    /**
     * @param Environment $environment
     *
     * @return array|null
     */
    public function resource(Environment $environment = null)
    {
        if (is_null($environment)) {
            return null;
        }

        $data = [
            'id' => $environment->id(),
            'name' => $environment->name(),
            'isProduction' => $environment->isProduction()
        ];

        $embedded = [];

        $links = ['self' => $this->link($environment)];

        return $this->buildResource($data, $embedded, $links);
    }
}
