<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Server;

/**
 *
 */
class ServerNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed;

    public function __construct(

    ) {


        $this->embed = [];
    }

    /**
     * @param Server $input
     * @return array
     */
    public function link(Server $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param Server $input
     * @param array $embed
     * @return array
     */
    public function resource(Server $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}