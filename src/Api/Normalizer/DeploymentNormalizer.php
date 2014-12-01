<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;

/**
 *
 */
class DeploymentNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed;

    public function __construct(

    ) {


        $this->embed = [];
    }

    /**
     * @param Deployment $input
     * @return array
     */
    public function link(Deployment $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Deployment $input
     * @param array $embed
     * @return array
     */
    public function resource(Deployment $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}