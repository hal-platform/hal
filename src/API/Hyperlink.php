<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API;

use JsonSerializable;

class Hyperlink
{
    /**
     * Href can be of the following format:
     *
     *  - 'http://full/url/to/some/resource',
     *  - 'route_name'
     *  - ['route_name', [ route parameters ]]
     *  - ['route_name', [ route parameters ], [ url get parameters ]]
     *
     * @var string|array
     */
    private $href;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string|array $href
     * @param string $title
     * @param string $type
     */
    public function __construct($href, string $title = '', string $type = '')
    {
        $this->href = $href;
        $this->title = $title;
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function href()
    {
        return $this->href;
    }

    /**
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [
            'href' => $this->href,
        ];

        if ($title = $this->title()) {
            $data['title'] = $title;
        }

        if ($type = $this->type()) {
            $data['type'] = $type;
        }

        return $data;
    }
}
