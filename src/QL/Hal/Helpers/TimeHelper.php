<?php

namespace QL\Hal\Helpers;

use DateInterval;
use DateTime;
use DateTimeZone;
use MCP\DataType\Time\TimeInterval;
use MCP\DataType\Time\TimePoint;

/**
 * Time Helper
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class TimeHelper
{
    const OUTPUT_TIMEZONE = 'America/Detroit';

    const OUTPUT_FORMAT_DATE = 'M j, Y g:i A';

    const OUTPUT_REL_CUTOFF = 'P7D';

    const INPUT_FORMAT_DATE_MYSQL = 'Y-m-d G:i:s';

    /**
     * Format a date/time for display
     *
     * @param mixed $value
     * @param string $format
     * @param string $timezone
     * @return string
     */
    public function format(
        $value,
        $format = self::OUTPUT_FORMAT_DATE,
        $timezone = self::OUTPUT_TIMEZONE
    ) {
        if ($time = $this->timepointConvert($value)) {
            return $time->format($format, $timezone);
        }

        return '';
    }

    /**
     * Format a date/time for display as a relative time. Dates that are further in the past than cuttoff will
     * be formatted by the $this->format() instead.
     *
     * @param mixed $value
     * @param string $cutoff
     * @param string $format
     * @param string $timezone
     * @return string
     */
    public function relative(
        $value,
        $cutoff = self::OUTPUT_REL_CUTOFF,
        $format = self::OUTPUT_FORMAT_DATE,
        $timezone = self::OUTPUT_TIMEZONE
    ) {
        if ($time = $this->timepointConvert($value)) {

            $now = $this->timepointConvert(new DateTime('now', new DateTimeZone('UTC')));
            $diff = (int)$now->format('U', self::OUTPUT_TIMEZONE) - (int)$time->format('U', self::OUTPUT_TIMEZONE);

            // yesterday
            // @todo

            // last week??
            // @todo

            // last month????
            // @todo

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
            // 5 days
            if ($diff > (60 * 60 * 24 * 5)) {
                return sprintf('%s days ago', ceil($diff/60/60/24));
            }

            // fall back to normal output
            return $time->format($format, $timezone);
        }

        return '';
    }

    /**
     * Convert several known date/time formats into a TimePoint object
     *
     * @param mixed $value
     * @return TimePoint|null
     */
    public function timepointConvert($value)
    {
        // Timepoint
        if ($value instanceof TimePoint) {
            return $value;
        }

        // String
        if (is_string($value)) {
            $time = DateTime::createFromFormat(
                'Y-m-d G:i:s',
                $value,
                new DateTimeZone('UTC')
            );

            if ($time === false) {
                $time = new DateTime(
                    $value,
                    new DateTimeZone('UTC')
                );
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

    /**
     * Convert several known date/time formats into a DateTime object
     *
     * @param mixed $value
     * @return DateTime|null
     */
    public function datetimeConvert($value)
    {


        return null;
    }
}
