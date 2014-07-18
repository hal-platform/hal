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

class DeploymentNormalizer
{
    /**
     * @type ApiHelper
     */
    private $api;

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
     * @param RepositoryNormalizer $repoNormalizer
     * @param ServerNormalizer $serverNormalizer
     */
    public function __construct(
        ApiHelper $api,
        RepositoryNormalizer $repoNormalizer,
        ServerNormalizer $serverNormalizer
    ) {
        $this->api = $api;
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
    public function linked(Deployment $deployment)
    {
        return $this->api->parseLink([
            'href' => ['api.deployment', ['id' => $deployment->getId()]]
        ]);
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
            'path' => $deployment->getPath()
        ];

        return array_merge_recursive(
            $content,
            $this->links($deployment),
            $this->normalizeRepository($deployment->getRepository(), $criteria['repository']),
            $this->normalizeServer($deployment->getServer(), $criteria['server'])
        );
    }

    /**
     * @param Deployment $deployment
     * @return array
     */
    private function links(Deployment $deployment)
    {
        $links =  [
            '_links' => [
                'self' => $this->linked($deployment),
                'lastPush' => $this->api->parseLink([
                    'href' => [
                        'api.deployment.lastpush', ['id' => $deployment->getId()]
                    ]
                ]),
                'lastSuccessfulPush' => $this->api->parseLink([
                    'href' => [
                        'api.deployment.lastpush', ['id' => $deployment->getId()], ['status' => 'Success']
                    ]
                ]),
                'index' => $this->api->parseLink([
                    'href' => [
                        'api.deployments', ['id' => $deployment->getRepository()->getId()]
                    ]
                ])
            ]
        ];

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
            $normalized = $this->repoNormalizer->linked($repository);
            $type = '_links';

        } else {
            $normalized = $this->repoNormalizer->normalize($repository, $criteria);
            $type = '_embedded';
        }

        return [
            $type => [
                'repository' => $normalized
            ]
        ];
    }

    /**
     * @param Server $server
     * @param array|null $criteria
     * @return array
     */
    private function normalizeServer(Server $server, $criteria)
    {
        if ($criteria === null) {
            $normalized = $this->serverNormalizer->linked($server);
            $type = '_links';

        } else {
            $normalized = $this->serverNormalizer->normalize($server, $criteria);
            $type = '_embedded';
        }

        return [
            $type => [
                'server' => $normalized
            ]
        ];
    }
}
