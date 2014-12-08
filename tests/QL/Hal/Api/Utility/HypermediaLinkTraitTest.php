<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Utility;

use PHPUnit_Framework_TestCase;

/**
 *
 */
class HypermediaLinkTraitTest extends PHPUnit_Framework_TestCase
{
    use HypermediaLinkTrait;

    public function testBuildLink()
    {
        $href = 'foobar';
        $properties = ['a' => 'b', 'c' => 'd'];

        $this->assertEquals(
            [
                'href' => $href,
            ] + $properties,
            $this->buildLink($href, $properties)
        );
    }
}