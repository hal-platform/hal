<?php

namespace QL\Hal\Api\Utility;

/**
 * Embedded Resource Resolution Trait
 */
trait EmbeddedResolutionTrait
{
    /**
     * Resolve any requested resources by property name
     *
     * @param array $properties
     * @param array $requested
     * @return array
     */
    private function resolveEmbedded(array $properties, array $requested)
    {
        $embedded = [];

        foreach ($properties as $property => $value) {
            if (in_array($property, $requested)) {
                $embedded[$property] = $value;
            }
        }

        return $embedded;
    }
}