<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api;

interface NormalizerInterface
{
    /**
     * @param $input
     *
     * @return mixed
     */
    public function normalize($input);
}
