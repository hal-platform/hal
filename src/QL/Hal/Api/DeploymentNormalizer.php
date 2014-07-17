<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;

class DeploymentNormalizer
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
     * @type RepositoryNormalizer
     */
    private $repoNormalizer;

    /**
     * @type ServerNormalizer
     */
    private $serverNormalizer;

    /**
     * @type array
     */
    private $standardCriteria;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param RepositoryNormalizer $repoNormalizer
     * @param ServerNormalizer $serverNormalizer
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        RepositoryNormalizer $repoNormalizer,
        ServerNormalizer $serverNormalizer
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->repoNormalizer = $repoNormalizer;
        $this->serverNormalizer = $serverNormalizer;

        $this->standardCriteria = [
            'repository' => null,
            'server' => null
        ];
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Deployment $deployment
     * @return array
     */
    public function normalizeLinked(Deployment $deployment)
    {
        $content = [
            'id' => $deployment->getId()
        ];

        $content = array_merge($content, $this->links($deployment));

        return $content;
    }

    /**
     * Normalize to the fully linked properties.
     *
     * If specified in criteria, linked resources will be fully resolved.
     *
     * @param Deployment $deployment
     * @param array $criteria
     * @return array
     */
    public function normalize(Deployment $deployment, array $criteria = [])
    {
        $criteria = array_merge($this->standardCriteria, $criteria);

        $content = [
            'id' => $deployment->getId(),
            'path' => $deployment->getPath(),
            'repository' => $this->normalizeRepository($deployment->getRepository(), $criteria['repository']),
            'server' => $this->normalizeServer($deployment->getServer(), $criteria['server'])
        ];

        $content = array_merge($content, $this->links($deployment));

        return $content;
    }

    /**
     * @param Deployment $deployment
     * @return array
     */
    private function links(Deployment $deployment)
    {
        $links = [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.deployment', ['id' => $deployment->getId()]]],
                'lastPush' => ['href' => ['api.deployment.lastpush', ['id' => $deployment->getId()]], 'type' => 'Last Push'],
                'lastSuccessfulPush' => ['href' => ['api.deployment.lastpush', ['id' => $deployment->getId()]], 'type' => 'Last Successful Push'],
            ])
        ];

        $links['_links']['lastSuccessfulPush']['href'] .= '?status=Success';
        return $links;
    }

    /**
     * @param Repository $repository
     * @param array|null $criteria
     * @return array
     */
    private function normalizeRepository(Repository $repository, $criteria)
    {
        if ($criteria === null) {
            return $this->repoNormalizer->normalizeLinked($repository);
        }

        return $this->repoNormalizer->normalize($repository, $criteria);
    }

    /**
     * @param Server $server
     * @param array|null $criteria
     * @return array
     */
    private function normalizeServer(Server $server, $criteria)
    {
        if ($criteria === null) {
            return $this->serverNormalizer->normalizeLinked($server);
        }

        return $this->serverNormalizer->normalize($server, $criteria);
    }
}
