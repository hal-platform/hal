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
     * Normalize to the linked resource.
     *
     * @param Environment $environment
     * @return array
     */
    public function linked(Environment $environment)
    {
        return $this->api->parseLink([
            'href' => ['api.environment', ['id' => $environment->getId()]],
            'title' => $environment->getKey()
        ]);
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

        return array_merge_recursive($content, $this->links($environment));
    }

    /**
     * @param Environment $environment
     * @return array
     */
    private function links(Environment $environment)
    {
        return [
            '_links' => [
                'self' => $this->linked($environment),
                'index' => $this->api->parseLink(['href' => 'api.environments'])
            ]
        ];
    }
}
