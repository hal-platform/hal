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
use QL\Hal\Core\Type\EnumType\ServerEnum;

class DeploymentNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @type ApplicationNormalizer
     */
    private $appNormalizer;

    /**
     * @type ServerNormalizer
     */
    private $serverNormalizer;

    /**
     * @type array
     */
    private $embed;

    /**
     * @param ApplicationNormalizer $appNormalizer
     * @param ServerNormalizer $serverNormalizer
     */
    public function __construct(
        ApplicationNormalizer $appNormalizer,
        ServerNormalizer $serverNormalizer
    ) {
        $this->appNormalizer = $appNormalizer;
        $this->serverNormalizer = $serverNormalizer;

        $this->embed = [];
    }

    /**
     * @param Deployment $deployment
     * @return array
     */
    public function link(Deployment $deployment = null)
    {
        return (is_null($deployment)) ? null : $this->buildLink(
            ['api.deployment', ['id' => $deployment->id()]],
            [
                'title' => $this->formatDeploymentTitle($deployment)
            ]
        );
    }

    /**
     * @param Deployment $deployment
     * @param array $embed
     * @return array
     */
    public function resource(Deployment $deployment = null, array $embed = [])
    {
        if (is_null($deployment)) {
            return null;
        }

        $properties = [
            'application' => $deployment->application(),
            'server' => $deployment->server()
        ];

        return $this->buildResource(
            [
                'id' => $deployment->id(),
                'name' => $deployment->name(),

                'path' => $deployment->path(),

                'eb-environment' => $deployment->ebEnvironment(),
                'ec2-pool' => $deployment->ec2Pool(),
                's3-bucket' => $deployment->s3bucket(),
                's3-file' => $deployment->s3file(),

                'url' => $deployment->url(),
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($deployment),
                'application' => $this->appNormalizer->link($deployment->application()),
                'server' => $this->serverNormalizer->link($deployment->server()),
                'last-push' => $this->buildLink(['api.deployment.lastpush', ['id' => $deployment->id()]]),
                'last-successful-push' => $this->buildLink(['api.deployment.lastpush', ['id' => $deployment->id()], ['status' => 'Success']])
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
