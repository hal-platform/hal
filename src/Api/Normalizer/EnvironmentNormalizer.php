<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\NormalizerInterface;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
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
