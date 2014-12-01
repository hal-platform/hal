<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Environment;

/**
 *
 */
class EnvironmentNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed;

    public function __construct(

    ) {


        $this->embed = [];
    }

    /**
     * @param Environment $input
     * @return array
     */
    public function link(Environment $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Environment $input
     * @param array $embed
     * @return array
     */
    public function resource(Environment $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}