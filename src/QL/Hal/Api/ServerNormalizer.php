<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;

class ServerNormalizer
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
     * @type EnvironmentNormalizer
     */
    private $envNormalizer;

    /**
     * @type array
     */
    private $standardCriteria;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param EnvironmentNormalizer $envNormalizer
     */
    public function __construct(ApiHelper $api, UrlHelper $url, EnvironmentNormalizer $envNormalizer)
    {
        $this->api = $api;
        $this->url = $url;
        $this->envNormalizer = $envNormalizer;

        $this->standardCriteria = [
            'environment' => null
        ];
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Server $server
     * @return array
     */
    public function normalizeLinked(Server $server)
    {
        $content = [
            'id' => $server->getId()
        ];

        $content = array_merge($content, $this->links($server));

        return $content;
    }

    /**
     * Normalize to the full entity properties.
     *
     * If specified, linked resources will be fully resolved.
     *
     * @param Server $server
     * @return array
     */
    public function normalize(Server $server, array $criteria = [])
    {
        $criteria = array_merge($this->standardCriteria, $criteria);

        $content = [
            'id' => $server->getId(),
            'url' => $this->url->urlFor('server', ['id' => $server->getId()]),
            'name' => $server->getName(),
            'environment' => $this->normalizeEnvironment($server->getEnvironment(), $criteria['environment'])
        ];

        $content = array_merge($content, $this->links($server));

        return $content;
    }

    /**
     * @param Server $server
     * @return array
     */
    private function links(Server $server)
    {
        return [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.server', ['id' => $server->getId()]]]
            ])
        ];
    }

    /**
     * @param Environment $environment
     * @param array|null $criteria
     * @return array
     */
    private function normalizeEnvironment(Environment $environment, $criteria)
    {
        if ($criteria === null) {
            return $this->envNormalizer->normalizeLinked($environment);
        }

        return $this->envNormalizer->normalize($environment, $criteria);
    }
}
