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
     * Format links and content and prepare response object
     *
     * @param Response $response
     * @param array $links
     * @param $content
     */
    public function prepareResponse(Response &$response, array $links, $content)
    {
        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->body(json_encode([
            '_links' => $this->parseLinks($links),
            'content' => $content
        ]));
    }

    /**
     * Formats a collection of links
     *
     * @param array $links
     * @return array
     */
    private function parseLinks(array $links)
    {
        $parsed = [];

        foreach ($links as $type => $properties) {
            $link = [];
            foreach ($properties as $property => $value) {
                if ($property == 'href') {
                    if (is_array($value) && count($value) == 2) {
                        $link[$property] = $this->url->urlFor($value[0], $value[1]);
                    } else {
                        $link[$property] = $this->url->urlFor($value);
                    }
                } else {
                    $link[$property] = $value;
                }
            }
            $parsed[$type] = $link;
        }

        return $parsed;




        // take into account other types @todo
        // http://tools.ietf.org/html/draft-kelly-json-hal-03#section-4.1.2

        foreach ($links as $type => &$properties) {

            if (is_array($properties) && count($properties) == 2) {
                $url = $this->url->urlFor($properties[0], $properties[1]);
            } else {
                $url = $this->url->urlFor($properties);
            }

            $properties = [
                'href' => $url
            ];
        }

        return $links;
    }
}
