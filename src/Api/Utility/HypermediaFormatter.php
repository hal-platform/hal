<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Api\Utility;

use QL\Hal\Api\Hyperlink;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Route;

/**
 * Hypermedia Content Formatter
 */
class HypermediaFormatter
{
    /**
     * @type Url
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Route
     */
    private $currentRoute;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param Url $url
     * @param Request $request
     * @param Route $currentRoute
     * @param array $parameters
     */
    public function __construct(Url $url, Request $request, Route $currentRoute, array $parameters)
    {
        $this->url = $url;
        $this->request = $request;
        $this->currentRoute = $currentRoute;
        $this->parameters = $parameters;
    }

    /**
     * Parse a HAL Resource Object in the form of an array and place the parsed and JSON encoded data in the current
     * response object.
     *
     * @param array $content
     *
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
            $self = $this->url->absoluteUrlFor(
                $this->currentRoute->getName(),
                $this->parameters,
                $this->request->get()
            );

            $links['self'] = $self;
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

            if (!is_array($child)) {
                var_dump($child);die;
            }

            return ($this->arrayIsAssoc($child)) ? $this->parseResource($child) : $this->parseResources($child);
        }, $relations);
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
