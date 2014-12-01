<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\EventLog;

/**
 *
 */
class EventLogNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    private $embed;

    public function __construct(

    ) {


        $this->embed = [];
    }

    /**
     * @param EventLog $input
     * @return array
     */
    public function link(EventLog $input)
    {
        return $this->buildLink('', [

        ]);
    }

    /**
     * @param EventLog $input
     * @param array $embed
     * @return array
     */
    public function resource(EventLog $input, array $embed = [])
    {
        return $this->buildResource(
            [],
            [],
            []
        );
    }
}