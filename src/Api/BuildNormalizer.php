<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;

class BuildNormalizer
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
     * @type EnvironmentNormalizer
     */
    private $envNormalizer;

    /**
     * @type RepositoryNormalizer
     */
    private $repoNormalizer;

    /**
     * @type UserNormalizer
     */
    private $userNormalizer;

    /**
     * @type array
     */
    private $standardCriteria;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param TimeHelper $time
     * @param EnvironmentNormalizer $envNormalizer
     * @param RepositoryNormalizer $repoNormalizer
     * @param UserNormalizer $userNormalizer
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        TimeHelper $time,
        EnvironmentNormalizer $envNormalizer,
        RepositoryNormalizer $repoNormalizer,
        UserNormalizer $userNormalizer
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->time = $time;
        $this->envNormalizer = $envNormalizer;
        $this->repoNormalizer = $repoNormalizer;
        $this->userNormalizer = $userNormalizer;

        $this->standardCriteria = [
            'environment' => null,
            'repository' => null,
            'user' => null
        ];
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Build $build
     * @return array
     */
    public function linked(Build $build)
    {
        return $this->api->parseLink([
            'href' => ['api.build', ['id' => $build->getId()]]
        ]);
    }

    /**
     * Normalize to the fully linked properties.
     *
     * If specified in criteria, linked resources will be fully resolved.
     *
     * @param Build $build
     * @param array $criteria
     * @return array
     */
    public function normalize(Build $build, array $criteria = [])
    {
        $criteria = array_merge($this->standardCriteria, $criteria);

        $content = [
            'id' => $build->getId(),
            'url' => $this->url->urlFor('build', ['build' => $build->getId()]),
            'status' => $build->getStatus(),
            'created' => [
                'text' => $this->time->relative($build->getCreated(), false),
                'datetime' => $this->time->format($build->getCreated(), false, 'c')
            ],
            'start' => [
                'text' => $this->time->relative($build->getStart(), false),
                'datetime' => $this->time->format($build->getStart(), false, 'c')
            ],
            'end' => [
                'text' => $this->time->relative($build->getEnd(), false),
                'datetime' => $this->time->format($build->getEnd(), false, 'c')
            ],
            'reference' => [
                'text' => $build->getBranch(),
                'url' => $this->url->githubReferenceUrl(
                    $build->getRepository()->getGithubUser(),
                    $build->getRepository()->getGithubRepo(),
                    $build->getBranch()
                )
            ],
            'commit' => [
                'text' => $build->getCommit(),
                'url' => $this->url->githubCommitUrl(
                    $build->getRepository()->getGithubUser(),
                    $build->getRepository()->getGithubRepo(),
                    $build->getCommit()
                )
            ]
        ];

        return array_merge_recursive(
            $content,
            $this->links($build),
            $this->normalizeRepository($build->getRepository(), $criteria['repository']),
            $this->normalizeEnvironment($build->getEnvironment(), $criteria['environment']),
            $this->normalizeUser($build->getUser(), $criteria['user'])
        );
    }

    /**
     * @param Build $build
     * @return array
     */
    private function links(Build $build)
    {
        return [
            '_links' => [
                'self' => $this->linked($build),
                //'log' => $this->api->parseLink(['href' => ['api.build.log', ['id' => $build->getId()]]]), // NO LOGS YET! :)
                'index' => $this->api->parseLink(['href' => ['api.builds', ['id' => $build->getRepository()->getId()]]]),
            ]
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
            $normalized = $this->envNormalizer->linked($environment);
            $type = '_links';

        } else {
            $normalized = $this->envNormalizer->normalize($environment, $criteria);
            $type = '_embedded';
        }

        return [
            $type => [
                'environment' => $normalized
            ]
        ];
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
     * The signature of this method is weird because we need User to be nullable.
     *
     * @param User|null $user
     * @param array|null $criteria
     * @return array
     */
    private function normalizeUser(User $user = null, $criteria)
    {
        if ($user === null) {
            return [];
        }

        if ($criteria === null) {
            $normalized = $this->userNormalizer->linked($user);
            $type = '_links';

        } else {
            $normalized = $this->userNormalizer->normalize($user, $criteria);
            $type = '_embedded';
        }

        return [
            $type => [
                'user' => $normalized
            ]
        ];
    }
}
