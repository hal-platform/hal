<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Utility;

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
