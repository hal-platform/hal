<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Helpers\ApiHelper;
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
     * @type GroupNormalizer
     */
    private $groupNormalizer;

    /**
     * @type array
     */
    private $standardCriteria;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param GroupNormalizer $groupNormalizer
     */
    public function __construct(ApiHelper $api, UrlHelper $url, GroupNormalizer $groupNormalizer)
    {
        $this->api = $api;
        $this->url = $url;
        $this->groupNormalizer = $groupNormalizer;

        $this->standardCriteria = [
            'group' => null
        ];
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Repository $repository
     * @return array
     */
    public function linked(Repository $repository)
    {
        return $this->api->parseLink([
            'href' => ['api.repository', ['id' => $repository->getId()]],
            'title' => $repository->getKey()
        ]);
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
        $criteria = array_merge($this->standardCriteria, $criteria);

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
            'buildTransformCmd' => $repository->getBuildTransformCmd(),
            'prePushCmd' => $repository->getPrePushCmd(),
            'postPushCmd' => $repository->getPostPushCmd()
        ];

        return array_merge_recursive(
            $content,
            $this->links($repository),
            $this->normalizeGroup($repository->getGroup(), $criteria['group'])
        );
    }

    /**
     * @param Repository $repository
     * @return array
     */
    private function links(Repository $repository)
    {
        return [
            '_links' => [
                'self' => $this->linked($repository),
                'deployments' => $this->api->parseLink(['href' => ['api.deployments', ['id' => $repository->getId()]]]),
                'builds' => $this->api->parseLink(['href' => ['api.builds', ['id' => $repository->getId()]]]),
                'pushes' => $this->api->parseLink(['href' => ['api.pushes', ['id' => $repository->getId()]]]),
                'tags' => $this->api->parseLink(['href' => ['api.repository.tags', ['id' => $repository->getId()]]]),
                'branches' => $this->api->parseLink(['href' => ['api.repository.branches', ['id' => $repository->getId()]]]),
                'pullRequests' => $this->api->parseLink(['href' => ['api.repository.pullrequests', ['id' => $repository->getId()]]]),
                'index' => $this->api->parseLink(['href' => 'api.repositories'])
            ]
        ];
    }

    /**
     * @param Group $group
     * @param array|null $criteria
     * @return array
     */
    private function normalizeGroup(Group $group, $criteria)
    {
        if ($criteria === null) {
            $normalized = $this->groupNormalizer->linked($group);
            $type = '_links';

        } else {
            $normalized = $this->groupNormalizer->normalize($group, $criteria);
            $type = '_embedded';
        }

        return [
            $type => [
                'group' => $normalized
            ]
        ];
    }
}
