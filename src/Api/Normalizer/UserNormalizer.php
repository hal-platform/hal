<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;

/**
 *
 */
class UserNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed = [];

    public function __construct(

    ) {

    }

    /**
     * @param User $input
     * @return array
     */
    public function link(User $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param User $input
     * @param array $embed
     * @return array
     */
    public function resource(User $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}