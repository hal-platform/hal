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

            // doesn't work right now
            // @todo make this work, use seconds instead since timepoint/timeinterval comparisons are dumb
            // fall back to normal for now
            return $time->format($format, $timezone);

            // from clock?
            $now = $this->timepointConvert(new DateTime('now', new DateTimeZone('UTC')));

            $interval = $time->diff($now);

            //var_dump($interval);

            // within seconds
            if ($time < ($now->sub(new TimeInterval('P1M')))) {
                return sprintf('%s seconds ago', $interval->format('%s'));
            }

            // within minutes

            // within hours, but still today

            // yesterday



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
