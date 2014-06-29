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
            return $this->html5time($time, $time->format($format, $timezone), $timezone);
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
                return $this->html5time($time, 'just a moment ago', $timezone);
            }
            // 90 seconds
            if ($diff < 90) {
                return $this->html5time($time, sprintf('%s seconds ago', $diff), $timezone);
            }
            // 60 minutes
            if ($diff < (60 * 60)) {
                return $this->html5time($time, sprintf('%s minutes ago', ceil($diff/60)), $timezone);
            }
            // 24 hours
            if ($diff < (60 * 60 * 24)) {
                return $this->html5time($time, sprintf('%s hours ago', ceil($diff/60/60)), $timezone);
            }
            // 28 days
            if ($diff < (60 * 60 * 24 * 28)) {
                return $this->html5time($time, sprintf('%s days ago', ceil($diff/60/60/24)), $timezone);
            }
            // 16 weeks
            if ($diff < (60 * 60 * 24 * 7 * 16)) {
                return $this->html5time($time, sprintf('%s weeks ago', ceil($diff/60/60/24/7)), $timezone);
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

    /**
     * Return an HTML5 time element for a given Timepoint and output text
     *
     * @param TimePoint $time
     * @param string $output
     * @param string $timezone
     * @return string
     */
    private function html5time(TimePoint $time, $output = '', $timezone = self::OUTPUT_TIMEZONE)
    {
        return sprintf(
            '<time datetime="%s">%s</time>',
            $time->format('Y-m-dTH:i:s', $timezone),
            $output
        );
    }
}
