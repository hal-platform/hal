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
     * @type array
     */
    private $standardCriteria;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param TimeHelper $time
     * @param EnvironmentNormalizer $envNormalizer
     * @param RepositoryNormalizer $repoNormalizer
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        TimeHelper $time,
        EnvironmentNormalizer $envNormalizer,
        RepositoryNormalizer $repoNormalizer
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->time = $time;
        $this->envNormalizer = $envNormalizer;
        $this->repoNormalizer = $repoNormalizer;

        $this->standardCriteria = [
            'environment' => null,
            'repository' => null
        ];
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Build $build
     * @return array
     */
    public function normalizeLinked(Build $build)
    {
        $content = [
            'id' => $build->getId()
        ];

        $content = array_merge($content, $this->links($build));

        return $content;
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
            ],
            'environment' => $this->normalizeEnvironment($build->getEnvironment(), $criteria['environment']),
            'repository' => $this->normalizeRepository($build->getRepository(), $criteria['repository']),
            'initiator' => [
                'user' => null,
                'consumer' => null
            ]
        ];

        // add initiators
        if ($build->getUser() instanceof User) {
            $content['initiator']['user'] = [
                'id' => $build->getUser()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.user', ['id' => $build->getUser()->getId()]], 'type' => 'User']
                ])
            ];
        }

        if ($build->getConsumer() instanceof Consumer) {
            $content['initiator']['consumer'] = [
                'id' => $build->getConsumer()->getId()
            ];
        }


        $content = array_merge($content, $this->links($build));

        return $content;
    }

    /**
     * @param Build $build
     * @return array
     */
    private function links(Build $build)
    {
        return [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.build', ['id' => $build->getId()]]],
                'log' => ['href' => ['api.build.log', ['id' => $build->getId()]], 'type' => 'Build Log']
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
}
