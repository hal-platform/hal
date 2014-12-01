<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Push;

/**
 *
 */
class PushNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed = [];

    public function __construct(

    ) {

    }

    /**
     * @param Push $input
     * @return array
     */
    public function link(Push $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Push $input
     * @param array $embed
     * @return array
     */
    public function resource(Push $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}