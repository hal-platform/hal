<?php

namespace QL\Hal\Api\Normalizer;

use MCP\DataType\HttpUrl;

/**
 * HttpUrl Object Normalizer
 */
class HttpUrlNormalizer
{
    /**
     * @param HttpUrl $url
     * @return string
     */
    public function normalize(HttpUrl $url = null)
    {
        return  (is_null($url)) ? null : $url->asString();
    }
}