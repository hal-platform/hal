<?php

namespace QL\Hal\Api\Utility;

use RuntimeException;
use QL\Hal\Helpers\UrlHelper;

/**
 * Hypermedia Content Formatter
 */
class HypermediaFormatter
{
    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param UrlHelper $url
     */
    public function __construct(UrlHelper $url)
    {
        $this->url = $url;
    }

    /**
     * Parse a HAL Resource Object in the form of an array and place the parsed and JSON encoded data in the current
     * response object.
     *
     * See parseLinks() and parseRelation() for details on how the special array keys '_links' and '_embedded' will
     * be handled, respectively.
     *
     * @param array $content
     * @return array
     */
    public function format(array $content)
    {
        $links = (isset($content['_links']) && is_array($content['_links'])) ? $content['_links'] : [];
        $embedded = (isset($content['_embedded']) && is_array($content['_embedded'])) ? $content['_embedded'] : [];

        unset($content['_links']);
        unset($content['_embedded']);

        // force self link
        if (!isset($links['self'])) {
            $links = ['self' => ['href' => $this->url->url()]] + $links;
        }

        // hide embedded if empty
        $embedded = (count($embedded)) ? ['_embedded' => $embedded] : [];

        // force order
        $content = ['_links' => $links] + $embedded + $content;

        return $this->parseResource($content);
    }

    /**
     * Parse an array of HAL Resource Objects
     *
     * [
     *      [
     *          // HAL Resource Object
     *          property1 => value1,
     *          property2 => value2
     *      ],
     *      [
     *          // HAL Resource Object
     *          property1 => value1,
     *          property2 => value2
     *      ]
     * ]
     *
     * @param array $content
     * @return array
     */
    public function parseResources(array $content)
    {
        return array_map(function ($resource) {
            return $this->parseResource($resource);
        }, $content);
    }

    /**
     * Parse a single HAL Resource Object
     *
     * [
     *      // HAL Resource Object
     *      property1 => value1,
     *      property2 => value2
     * ]
     *
     * @param array $content
     * @return array
     */
    public function parseResource(array $content)
    {
        array_walk($content, function (&$value, $key) {
            switch($key) {
                case '_embedded':
                    $value = $this->parseRelations($value);
                    break;
                case '_links':
                    $value = $this->parseLinks($value);
                    break;
            }
        });

        return $content;
    }

    /**
     * Parse an array of embedded HAL Resource Objects in the following format
     *
     * [
     *      'relation1' => [
     *          // HAL Resource Object
     *          property1 => value1,
     *          property2 => value2
     *      ]
     *      'relation2' => [
     *          // HAL Resource Object
     *          property1 => value1,
     *          property2 => value2
     *      ]
     * ]
     *
     * Or an array of embedded HAL Resource object collections in the following format
     *
     * [
     *      'relation1' => [
     *          [
     *              // HAL Resource Object
     *              property1 => value1,
     *              property2 => value2
     *          ],
     *          [
     *              // HAL Resource Object
     *              property1 => value1,
     *              property2 => value2
     *          ]
     *      ],
     *      'relation2' => [
     *          [
     *              // HAL Resource Object
     *              property1 => value1,
     *              property2 => value2
     *          ],
     *          [
     *              // HAL Resource Object
     *              property1 => value1,
     *              property2 => value2
     *          ]
     *      ]
     * ]
     *
     * More details on relation types can be found in RFC 5988
     * http://tools.ietf.org/html/rfc5988
     *
     * @param array $relations
     * @return array
     */
    public function parseRelations(array $relations)
    {
        return array_map(function ($child) {

            if (is_null($child)) {
                return null;
            }

            return ($this->arrayIsAssoc($child)) ? $this->parseResource($child) : $this->parseResources($child);
        }, $relations);
    }

    /**
     * Parse a collection of links
     *
     * [
     *      [
     *          // HAL Link
     *          property1 => value1,
     *          property2 => value2
     *      ],
     *      [
     *          // HAL Link
     *          property1 => value1,
     *          property2 => value2
     *      ]
     * ]
     *
     * @param array $links
     * @return array
     */
    public function parseLinks(array $links)
    {
        return array_map(function ($link) {
            return (is_null($link)) ? null : $this->parseLink($link);
        }, $links);
    }

    /**
     * Parse a link with the following format
     *
     * [
     *      // HAL Link
     *      property1 => value1,
     *      property2 => value2
     * ]
     *
     * Or a collection of links in the following format
     *
     * [
     *      [
     *          // HAL Link
     *          property1 => value1,
     *          property2 => value2
     *      ],
     *      [
     *          // HAL Link
     *          property1 => value1,
     *          property2 => value2
     *      ]
     * ]
     *
     * Properties may be any of the following
     *
     *  - href (array)
     *  - templated (boolean)
     *  - type (string)
     *  - deprecation (string)
     *  - name (string)
     *  - profile (string)
     *  - title (string)
     *
     * If the property is 'href', then it must follow one of the following formats
     *
     *  - 'http://full/url/to/some/resource',
     *  - 'route_name'
     *  - ['route_name', [ route parameters ]]
     *  - ['route_name', [ route parameters ], [ url get parameters ]]
     *  - ['route_name', [ route parameters ], [ url get parameters ], 'url_fragment']
     *
     * @param array $properties
     * @return array
     */
    public function parseLink(array &$properties = null)
    {
        if ($this->arrayIsAssoc($properties)) {
            // single link
            foreach ($properties as $property => &$value) {
                if ($property === 'href') {
                    if (is_array($value)) {
                        $url = $this->url->urlFor($value[0], $value[1]);
                        if (count($value) > 2) {
                            $url = sprintf('%s?%s', $url, http_build_query($value[2]));
                        }
                        if (count($value) > 3) {
                            $url = sprintf('%s#%s', $url, $value[3]);
                        }
                        $value = $url;
                    } else {
                        try {
                            $value = $this->url->urlFor($value);
                        } catch (RuntimeException $e) {
                            // do nothing, pass $value through without modification
                        }
                    }
                }
            }
        } else {
            // collection of links
            return $this->parseLinks($properties);
        }

        return $properties;
    }

    /**
     * Check if an array is associative
     *
     * @param array $data
     * @return bool
     */
    private function arrayIsAssoc(array $data)
    {
        return ($data !== array_values($data));
    }
}