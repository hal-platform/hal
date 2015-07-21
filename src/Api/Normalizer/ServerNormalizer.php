<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Core\Utility\SortingTrait;

class ServerNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;
    use SortingTrait;

    /**
     * @type EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $embed;

    /**
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(EnvironmentNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;

        $this->embed = ['environment'];
    }

    /**
     * @param Server $server
     * @return array
     */
    public function link(Server $server = null)
    {
        return (is_null($server)) ? null :$this->buildLink(
            ['api.server', ['id' => $server->id()]],
            [
                'title' => $this->formatServerTitle($server)
            ]
        );
    }

    /**
     * @param Server $server
     * @param array $embed
     * @return array
     */
    public function resource(Server $server = null, array $embed = [])
    {
        if (is_null($server)) {
            return null;
        }

        $properties = [
            'environment' => $server->environment(),
            'deployments' => $server->deployments()->toArray()
        ];

        // shitty, but this is a circular reference so deal with it
        $linkedDeployments = [];
        $deployments = $server->deployments()->toArray();
        usort($deployments, $this->deploymentSorter());
        foreach ($deployments as $deployment) {
            $linkedDeployments[] = $this->buildLink(
                ['api.deployment', ['id' => $deployment->id()]],
                ['title' => $this->formatDeploymentTitle($deployment)]
            );
        }

        return $this->buildResource(
            [
                'id' => $server->id(),
                'type' => $server->type(),
                'name' => $server->name()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($server),
                'environment' => $this->normalizer->link($server->environment()),
                'deployments' => $linkedDeployments
            ]
        );
    }

    /**
     * @param Server $server
     *
     * @return string
     */
    private function formatServerTitle(Server $server)
    {
        $type = $server->type();

        if ($type === ServerEnum::TYPE_EB) {
            return sprintf('EB (%s)', $server->name());

        } elseif ($type === ServerEnum::TYPE_EC2) {
            return sprintf('EC2 (%s)', $server->name());

        } elseif ($type === ServerEnum::TYPE_S3) {
            return sprintf('S3 (%s)', $server->name());
        }

        return $server->name();
    }

    /**
     * @param Deployment $deployment
     *
     * @return string
     */
    private function formatDeploymentTitle(Deployment $deployment)
    {
        if ($deployment->name()) {
            return $deployment->name();
        }

        $type = $deployment->server()->type();

        if ($type === ServerEnum::TYPE_EB) {
            return sprintf('EB (%s)', $deployment->ebEnvironment());

        } elseif ($type === ServerEnum::TYPE_EC2) {
            return sprintf('EC2 (%s)', $deployment->ec2Pool());

        } elseif ($type === ServerEnum::TYPE_S3) {
            return sprintf('S3 (%s)', $deployment->s3bucket());

        } else {
            return sprintf('RSync (%s)', $deployment->path());
        }
    }
}
