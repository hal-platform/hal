<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository;

/**
 *
 */
class RepositoryNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed = [];

    public function __construct(

    ) {

    }

    /**
     * @param Repository $input
     * @return array
     */
    public function link(Repository $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Repository $input
     * @param array $embed
     * @return array
     */
    public function resource(Repository $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}