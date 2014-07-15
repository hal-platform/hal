<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Repository;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;

class RepositoryNormalizer
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
     * @type TimeHelper
     */
    private $time;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param TimeHelper $time
     */
    public function __construct(ApiHelper $api, UrlHelper $url, TimeHelper $time)
    {
        $this->api = $api;
        $this->url = $url;
        $this->time = $time;
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Repository $repository
     * @return array
     */
    public function normalizeLinked(Repository $repository)
    {
        $content = [
            'id' => $repository->getId()
        ];

        $content = array_merge($content, $this->links($repository));

        return $content;
    }

    /**
     * Normalize to the full entity properties.
     *
     * If specified, linked resources will be fully resolved.
     *
     * @param Repository $repository
     * @return array
     */
    public function normalize(Repository $repository, array $criteria = [])
    {
        $content = [
            'id' => $repository->getId(),
            'url' => $this->url->urlFor('repository', ['id' => $repository->getId()]),
            'key' => $repository->getKey(),
            'description' => $repository->getDescription(),
            'email' => $repository->getEmail(),
            'githubUser' => [
                'text' => $repository->getGithubUser(),
                'url' => $this->url->githubUserUrl($repository->getGithubUser())
            ],
            'githubRepo' => [
                'text' => $repository->getGithubRepo(),
                'url' => $this->url->githubRepoUrl($repository->getGithubUser(), $repository->getGithubRepo())
            ],
            'buildCmd' => $repository->getBuildCmd(),
            'prePushCmd' => $repository->getPrePushCmd(),
            'postPushCmd' => $repository->getPostPushCmd(),

            // @todo make a normalizer for groups!
            'group' => [
                'id' => $repository->getGroup()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.group', ['id' => $repository->getGroup()->getId()]], 'type' => 'Group']
                ])
            ]
        ];

        $content = array_merge($content, $this->links($repository));

        return $content;
    }

    /**
     * @param Repository $repository
     * @return array
     */
    private function links(Repository $repository)
    {
        return [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.repository', ['id' => $repository->getId()]]],
                'deployments' => ['href' => ['api.deployments', ['id' => $repository->getId()]], 'type' => 'Deployments'],
                'builds' => ['href' => ['api.builds', ['id' => $repository->getId()]], 'type' => 'Builds'],
                'pushes' => ['href' => ['api.pushes', ['id' => $repository->getId()]], 'type' => 'Pushes']
            ])
        ];
    }
}
