<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Group;

/**
 *
 */
class GroupNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed = [];

    public function __construct(

    ) {

    }

    /**
     * @param Group $input
     * @return array
     */
    public function link(Group $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Group $input
     * @param array $embed
     * @return array
     */
    public function resource(Group $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}