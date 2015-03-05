<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use DateTime;
use DateTimeZone;
use MCP\DataType\Time\Clock;
use MCP\DataType\Time\TimePoint;

class TimeHelper
{
    const OUTPUT_FORMAT_DATE = 'M j, Y g:i A';

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @type string
     */
    private $timezone;

    /**
     * @param Clock $clock
     * @param string $timezone
     */
    public function __construct(Clock $clock, $timezone)
    {
        $this->clock = $clock;
        $this->timezone = $timezone;
    }

    /**
     * Format a date/time for display
     *
     * @param mixed $value
     * @param string $format
     *
     * @return string
     */
    public function format($value, $format = '')
    {
        $format = $format ?: self::OUTPUT_FORMAT_DATE;

        if (!$time = $this->convertToTimepoint($value)) {
            return '';
        }

        return $time->format($format, $this->timezone);
    }

    /**
     * Return an HTML5 time element for a given Timepoint and output text
     *
     * @param TimePoint|null $time
     * @param string $format
     *
     * @return string
     */
    public function html5(TimePoint $time = null, $format = '')
    {
        $format = $format ?: self::OUTPUT_FORMAT_DATE;

        if ($time === null) {
            return '';
        }

        return sprintf(
            '<time datetime="%s">%s</time>',
            $time->format(DateTime::RFC3339, 'UTC'),
            $time->format(self::OUTPUT_FORMAT_DATE, $this->timezone)
        );
    }


    /**
     * Format a date/time for display as a relative time. Dates that are further in the past than cuttoff will
     * be formatted by the $this->format() instead.
     *
     * @param mixed $value
     * @param string $format
     *
     * @return string
     */
    public function relative($value, $format = '')
    {
        $format = $format ?: self::OUTPUT_FORMAT_DATE;

        if (!$time = $this->convertToTimepoint($value)) {
            return '';
        }

        $now = $this->clock->read();
        $diff = (int) $now->format('U', $this->timezone) - (int) $time->format('U', $this->timezone);

        // 10 seconds
        if ($diff < 10) {
            return 'just a moment ago';
        }

        // 90 seconds
        if ($diff < 90) {
            return sprintf('%s seconds ago', $diff);
        }

        // 60 minutes
        if ($diff < (60 * 60)) {
            return sprintf('%s minutes ago', ceil($diff/60));
        }

        // 24 hours
        if ($diff < (60 * 60 * 24)) {
            return sprintf('%s hours ago', ceil($diff/60/60));
        }

        // 20 days
        if ($diff < (60 * 60 * 24 * 20)) {
            return sprintf('%s days ago', ceil($diff/60/60/24));
        }

        // 6 weeks
        if ($diff < (60 * 60 * 24 * 7 * 6)) {
            return sprintf('%s weeks ago', ceil($diff/60/60/24/7));
        }

        // fall back to normal output
        return $time->format($format, $this->timezone);
    }

    /**
     * Convert several known date/time formats into a TimePoint object
     *
     * @param mixed $value
     * @return TimePoint|null
     */
    public function convertToTimepoint($value)
    {
        // Timepoint
        if ($value instanceof TimePoint) {
            return $value;
        }

        // String
        if (is_string($value)) {
            $time = DateTime::createFromFormat('Y-m-d G:i:s', $value, new DateTimeZone('UTC'));

            if ($time === false) {
                $time = new DateTime($value, new DateTimeZone('UTC'));
            }

            $value = $time;
        }

        // Datetime
        if ($value instanceof DateTime) {
            return new TimePoint(
                $value->format('Y'),
                $value->format('m'),
                $value->format('d'),
                $value->format('G'),
                $value->format('i'),
                $value->format('s'),
                $value->getTimezone()->getName()
            );
        }

        return null;
    }
}
