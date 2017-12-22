<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API;

interface ResourceNormalizerInterface extends NormalizerInterface
{
    /**
     * Create a hypermedia resource from the input entity.
     *
     * @param mixed $input
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($input, array $embed = []): ?HypermediaResource;
}
