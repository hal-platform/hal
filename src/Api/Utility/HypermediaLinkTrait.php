<?php

namespace QL\Hal\Api\Utility;

/**
 * Hypermedia Link Generation Trait
 */
trait HypermediaLinkTrait
{
    /**
     * Build a hypermedia link
     *
     * @param string $href
     * @param array $properties
     * @return array
     */
    private function buildLink($href, array $properties)
    {
        return [
            'href' => $href
        ] + $properties;
    }
}