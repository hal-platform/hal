<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Api\Normalizer;

use DateTime;
use QL\Hal\Api\NormalizerInterface;
use QL\MCP\Common\Time\TimePoint;

class TimePointNormalizer implements NormalizerInterface
{
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
     * @param TimePoint $time
     *
     * @return string|null
     */
    public function normalize($time)
    {
        if (!$time instanceof TimePoint) {
            return null;
        }

        $formatted = $time->format($this->format, $this->timezone);

        return str_replace('+0000', 'Z', $formatted);
    }
}
