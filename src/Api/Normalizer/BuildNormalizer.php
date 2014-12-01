<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;

/**
 *
 */
class BuildNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed = [];

    public function __construct(

    ) {

    }

    /**
     * @param Build $input
     * @return array
     */
    public function link(Build $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Build $input
     * @param array $embed
     * @return array
     */
    public function resource(Build $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}