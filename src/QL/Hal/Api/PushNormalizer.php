<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;

class PushNormalizer
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
     * @type BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @type DeploymentNormalizer
     */
    private $deploymentNormalizer;

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
     * @param BuildNormalizer $buildNormalizer
     * @param DeploymentNormalizer $deploymentNormalizer
     * @param UserNormalizer $userNormalizer
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        TimeHelper $time,
        BuildNormalizer $buildNormalizer,
        DeploymentNormalizer $deploymentNormalizer,
        UserNormalizer $userNormalizer
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->time = $time;
        $this->buildNormalizer = $buildNormalizer;
        $this->deploymentNormalizer = $deploymentNormalizer;
        $this->userNormalizer = $userNormalizer;

        $this->standardCriteria = [
            'build' => null,
            'deployment' => null,
            'user' => null
        ];
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Push $push
     * @return array
     */
    public function normalizeLinked(Push $push)
    {
        $content = [
            'id' => $push->getId()
        ];

        $content = array_merge($content, $this->links($push));

        return $content;
    }

    /**
     * Normalize to the fully linked properties.
     *
     * If specified in criteria, linked resources will be fully resolved.
     *
     * @param Push $push
     * @param array $criteria
     * @return array
     */
    public function normalize(Push $push, array $criteria = [])
    {
        $criteria = array_merge($this->standardCriteria, $criteria);

        $content = [
            'id' => $push->getId(),
            'url' => $this->url->urlFor('push', ['push' => $push->getId()]),
            'status' => $push->getStatus(),
            'created' => [
                'text' => $this->time->relative($push->getCreated(), false),
                'datetime' => $this->time->format($push->getCreated(), false, 'c')
            ],
            'start' => [
                'text' => $this->time->relative($push->getStart(), false),
                'datetime' => $this->time->format($push->getStart(), false, 'c')
            ],
            'end' => [
                'text' => $this->time->relative($push->getEnd(), false),
                'datetime' => $this->time->format($push->getEnd(), false, 'c')
            ],
            'build' => $this->normalizeBuild($push->getBuild(), $criteria['build']),
            'deployment' => $this->normalizeDeployment($push->getDeployment(), $criteria['deployment']),
            'initiator' => [
                'user' => $this->normalizeUser($push->getUser(), $criteria['user']),
                'consumer' => null
            ]
        ];

        $content = array_merge($content, $this->links($push));

        return $content;
    }

    /**
     * @param Push $push
     * @return array
     */
    private function links(Push $push)
    {
        return [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.push', ['id' => $push->getId()]]],
                'log' => ['href' => ['api.push.log', ['id' => $push->getId()]], 'type' => 'Push Log'],
            ])
        ];
    }

    /**
     * @param Build $build
     * @param array|null $criteria
     * @return array
     */
    private function normalizeBuild(Build $build, $criteria)
    {
        if ($criteria === null) {
            return $this->buildNormalizer->normalizeLinked($build);
        }

        return $this->buildNormalizer->normalize($build, $criteria);
    }

    /**
     * @param Deployment $deployment
     * @param array|null $criteria
     * @return array
     */
    private function normalizeDeployment(Deployment $deployment, $criteria)
    {
        if ($criteria === null) {
            return $this->deploymentNormalizer->normalizeLinked($deployment);
        }

        return $this->deploymentNormalizer->normalize($deployment, $criteria);
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
            return null;
        }

        if ($criteria === null) {
            return $this->userNormalizer->normalizeLinked($user);
        }

        return $this->userNormalizer->normalize($user, $criteria);
    }
}
