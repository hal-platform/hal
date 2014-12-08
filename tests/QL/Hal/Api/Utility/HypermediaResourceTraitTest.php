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
class HypermediaResourceTraitTest extends PHPUnit_Framework_TestCase
{
    use HypermediaResourceTrait;

    /**
     * @dataProvider data
     */
    public function testBuildResource(array $expected, array $data, array $embedded, array $links)
    {
        $this->assertEquals($expected, $this->buildResource($data, $embedded, $links));
    }

    public function data()
    {
        return [
            // no links or embedded
            [
                [
                    'foo' => 'bar'
                ],
                [
                    'foo' => 'bar'
                ],
                [],
                []
            ],
            // links only
            [
                [
                    '_links' => [
                        'build' => 'buildlink'
                    ],
                    'foo' => 'bar'
                ],
                [
                    'foo' => 'bar'
                ],
                [],
                [
                    'build' => 'buildlink'
                ]
            ],
            // embedded only
            [
                [
                    '_embedded' => [
                        'build' => 'builddata'
                    ],
                    'foo' => 'bar'
                ],
                [
                    'foo' => 'bar'
                ],
                [
                    'build' => 'builddata'
                ],
                []
            ],
            // links and embedded
            [
                [
                    '_links' => [
                        'a' => 'b'
                    ],
                    '_embedded' => [
                        'c' => 'd'
                    ],
                    'foo' => 'bar'
                ],
                [
                    'foo' => 'bar'
                ],
                [
                    'c' => 'd'
                ],
                [
                    'a' => 'b'
                ]
            ],
            // links where duplicated in embedded
            [
                [
                    '_links' => [
                        'a' => 'b'
                    ],
                    '_embedded' => [
                        'c' => 'd'
                    ],
                    'foo' => 'bar'
                ],
                [
                    'foo' => 'bar'
                ],
                [
                    'c' => 'd'
                ],
                [
                    'a' => 'b',
                    'c' => 'd'
                ]
            ]
        ];
    }
}