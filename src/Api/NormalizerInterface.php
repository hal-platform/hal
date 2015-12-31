<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

interface NormalizerInterface
{
    /**
     * @param $input
     *
     * @return mixed
     */
    public function normalize($input);
}