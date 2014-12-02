<?php

namespace QL\Hal\Api\Normalizer;

use MCP\DataType\Time\TimePoint;
use QL\Hal\Helpers\TimeHelper;

/**
 * TimePoint Object Normalizer
 */
class TimePointNormalizer
{
    /**
     * @var TimeHelper
     */
    private $time;

    /**
     * @param TimeHelper $time
     */
    public function __construct(
        TimeHelper $time
    ) {
        $this->time = $time;
    }

    /**
     * @param TimePoint $time
     * @return array
     */
    public function normalize(TimePoint $time)
    {
        return [
            'display' => $this->time->relative($time, false),
            'datetime' => $this->time->format($time, false)
        ];
    }
}