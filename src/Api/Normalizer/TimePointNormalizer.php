<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use DateTime;
use MCP\DataType\Time\TimePoint;

class TimePointNormalizer
{
    const DEFAULT_FORMAT = 'c';
    const DEFAULT_TIMEZONE = 'UTC';

    /**
     * @type string
     */
    private $fomat;

    /**
     * @type string
     */
    private $timezone;

    /**
     * @param string $format
     * @param string $timezone
     */
    public function __construct($format = '', $timezone = self::DEFAULT_TIMEZONE)
    {
        if (!$format) {
            $format = DateTime::ISO8601;
        }

        $this->format = $format;
        $this->timezone = $timezone;
    }

    /**
     * @param TimePoint|null $time
     *
     * @return string|null
     */
    public function normalize(TimePoint $time = null)
    {
        if (!$time) {
            return null;
        }

        $formatted = $time->format($this->format, $this->timezone);

        return str_replace('+0000', 'Z', $formatted);
    }
}
