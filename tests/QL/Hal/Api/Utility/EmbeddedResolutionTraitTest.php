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
class EmbeddedResolutionTraitTest extends PHPUnit_Framework_TestCase
{
    use EmbeddedResolutionTrait;

    /**
     * @dataProvider data
     */
    public function testResolveEmbedded(array $expected, array $properties, array $requested)
    {
        $this->assertEquals($expected, $this->resolveEmbedded($properties, $requested));
    }

    public function data()
    {
        return [
            [
                [
                    'a' => 'aaaaaaaa'
                ],
                [
                    'a' => 'aaaaaaaa',
                    'b' => 'bbbbbbbb'
                ],
                [
                    'a'
                ]
            ]
        ];
    }
}