<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Environment;

/**
 * Environment Object Normalizer
 */
class EnvironmentNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    /**
     * @param Environment $environment
     * @return array
     */
    public function link(Environment $environment = null)
    {
        return (is_null($environment)) ? null : $this->buildLink(
            ['api.environment', ['id' => $environment->getId()]],
            [
                'title' => $environment->getKey()
            ]
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
                'id' => $environment->getId(),
                'key' => $environment->getKey(),
                'isProduction' => $environment->getIsProduction()
            ],
            [],
            [
                'self' => $this->link($environment)
            ]
        );
    }
}
