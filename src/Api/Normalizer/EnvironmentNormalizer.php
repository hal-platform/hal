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
    public function link(Environment $environment)
    {
        return $this->buildLink(
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
    public function resource(Environment $environment)
    {
        return $this->buildResource(
            [
                'id' => $environment->getId(),
                'key' => $environment->getKey(),
                'order' => $environment->getOrder()
            ],
            [],
            [
                'self' => $this->link($environment)
            ]
        );
    }
}