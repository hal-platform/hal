<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use JsonSerializable;

class Hyperlink
{
    /**
     *
     * Href can be of the following format:
     *
     *  - 'http://full/url/to/some/resource',
     *  - 'route_name'
     *  - ['route_name', [ route parameters ]]
     *  - ['route_name', [ route parameters ], [ url get parameters ]]
     *
     * @type string|array
     */
    private $href;

    /**
     * @type string|null
     */
    private $title;

    /**
     * @type string|null
     */
    private $type;

    // private $name;
    // private $templated;
    // private $deprecation;
    // private $profile;

    /**
     * @param string|array $href
     * @param string|null $title
     * @param string|null $type
     */
    public function __construct($href, $title = null, $type = null)
    {
        $this->href = $href;
        $this->title = $title;
        $this->type = $type;
    }

    /**
     * @return string
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
            'href' => $this->href
        ];

        $title = $this->title();
        if (is_string($title) && $title) {
            $data['title'] = $title;
        }

        $type = $this->type();
        if (is_string($type) && $type) {
            $data['type'] = $type;
        }

        return $data;
    }

    // /**
    //  * @return string
    //  */
    // public function name()
    // {
    //     return $this->name;
    // }

    // /**
    //  * @return bool
    //  */
    // public function templated()
    // {
    //     return $this->templated;
    // }

    // /**
    //  * @return string
    //  */
    // public function deprecation()
    // {
    //     return $this->deprecation;
    // }

    // /**
    //  * @return string
    //  */
    // public function profile()
    // {
    //     return $this->profile;
    // }
}
