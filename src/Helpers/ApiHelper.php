<?php

namespace QL\Hal\Helpers;

use Slim\Http\Response;

/**
 * Helper Class for API Endpoints
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class ApiHelper
{
    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param UrlHelper $url
     */
    public function __construct(
        UrlHelper $url
    ) {
        $this->url = $url;
    }

    /**
     * Format content and prepare response object
     *
     * @param Response $response
     * @param $content
     */
    public function prepareResponse(Response &$response, $content)
    {
        $response->header('Content-Type', 'application/hal+json; charset=utf-8');
        $response->body(json_encode(
            $content
        , JSON_UNESCAPED_SLASHES));
    }

    /**
     * Formats a link from properties.
     *
     * "href" can be in the following formats:
     *   - 'route.key'
     *   - ['route.key', [route.parameters]]
     *   - ['route.key', [route.parameters], [get.parameters]]
     *
     * @param array $properties
     * @return array
     */
    public function parseLink(array $properties)
    {
        foreach ($properties as $property => &$value) {
            if ($property == 'href') {
                if (is_array($value) && count($value) >= 2) {
                    $suffix = '';
                    if (isset($value[2])) {
                        $suffix .= '?' . http_build_query($value[2]);
                    }

                    $value = $this->url->urlFor($value[0], $value[1]) . $suffix;

                } else {
                    $value = $this->url->urlFor($value);
                }
            }
        }

        return $properties;
    }

    /**
     * Formats a collection of links
     *
     * @param array $links
     * @return array
     */
    public function parseLinks(array $links)
    {
        $parsed = [];

        foreach ($links as $relation => $properties) {
            $parsed[$relation] = $this->parseLink($properties);
        }

        return $parsed;
    }
}
