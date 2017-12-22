<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API;

use JsonSerializable;

interface NormalizerInterface
{
    /**
     * @param mixed $input
     *
     * @return array|JsonSerializable|HypermediaResource|Hyperlink|scalar
     */
    public function normalize($input);

    /**
     * Create a link for the input resource.
     *
     * @param mixed $input
     *
     * @return Hyperlink|null
     */
    public function link($input): ?Hyperlink;
}
