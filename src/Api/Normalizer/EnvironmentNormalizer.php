<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\NormalizerInterface;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Environment;

class EnvironmentNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;

    /**
     * @param Environment $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Environment $environment
     * @return array
     */
    public function link(Environment $environment = null)
    {
        if (!$environment) {
            return null;
        }

        return new Hyperlink(
            ['api.environment', ['id' => $environment->id()]],
            $environment->name()
        );
    }

    /**
     * @param Environment $environment
     * @return array
     */
    public function resource(Environment $environment = null)
    {
        if (is_null($environment)) {
            return null;
        }

        return $this->buildResource(
            [
                'id' => $environment->id(),
                'name' => $environment->name(),
                'isProduction' => $environment->isProduction()
            ],
            [],
            [
                'self' => $this->link($environment)
            ]
        );
    }
}
