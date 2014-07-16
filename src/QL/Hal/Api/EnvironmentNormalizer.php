<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Environment;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;

class EnvironmentNormalizer
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     */
    public function __construct(ApiHelper $api, UrlHelper $url)
    {
        $this->api = $api;
        $this->url = $url;
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Environment $environment
     * @return array
     */
    public function normalizeLinked(Environment $environment)
    {
        $content = [
            'id' => $environment->getId()
        ];

        $content = array_merge($content, $this->links($environment));

        return $content;
    }

    /**
     * Normalize to the full entity properties.
     *
     * If specified, linked resources will be fully resolved.
     *
     * @param Environment $environment
     * @return array
     */
    public function normalize(Environment $environment, array $criteria = [])
    {
        $content = [
            'id' => $environment->getId(),
            'url' => $this->url->urlFor('environment', ['id' => $environment->getId()]),
            'key' => $environment->getKey(),
            'order' => $environment->getOrder()
        ];

        $content = array_merge($content, $this->links($environment));

        return $content;
    }

    /**
     * @param Environment $environment
     * @return array
     */
    private function links(Environment $environment)
    {
        return [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.environment', ['id' => $environment->getId()]]]
            ])
        ];
    }
}
