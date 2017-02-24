<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Utility;

trait HypermediaResourceTrait
{
    /**
     * Build a hypermedia resource tree
     *
     * @param array $data
     * @param array $embedded
     * @param array $links
     * @return array
     */
    private function buildResource(array $data, array $embedded = [], array $links = [])
    {
        // dedupe links when an embedded entry exists
        foreach (array_keys($embedded) as $key) {
            unset($links[$key]);
        }

        if (count($embedded)) {
            $data = [
                '_embedded' => $embedded
            ] + $data;
        }

        if (count($links)) {
            $data = [
                '_links' => $links
            ] + $data;
        }

        return $data;
    }
}
