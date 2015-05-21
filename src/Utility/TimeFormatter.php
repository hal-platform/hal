<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Utility;

use DateTime;
use DateTimeZone;
use MCP\DataType\Time\Clock;
use MCP\DataType\Time\TimePoint;

class TimeFormatter
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

        $absolute = $time->format(DateTime::ISO8601, 'UTC');
        $absolute = str_replace('+0000', 'Z', $absolute);

        $relative = $this->relative($time);

        return sprintf(
            '<time datetime="%s">%s</time>',
            $absolute,
            $relative
        );
    }

    /**
     * Return an HTML5 time element with a duration
     *
     * @param TimePoint $start
     * @param TimePoint|null $end
     *
     * @return string
     */
    public function html5duration(TimePoint $start, TimePoint $end = null)
    {
        if (!$end) {
            $end = $this->clock->read();
        }

        $interval = $start->diff($end);

        $human = sprintf('%d seconds', $interval->format('%s'));
        if ($minutes = $interval->format('%i')) {
            $human = sprintf('%d minutes, ' , $minutes) . $human;
        }

        if ($interval->format('%h') || $interval->format('%d')) {
            $human = 'A really long time';
        }

        // Remove our "special" addition to the iso spec in TimeInterval
        $isoSpec = $interval->intervalSpec();
        $isoSpec = explode('-', $isoSpec);

        return sprintf(
            '<time datetime="%s">%s</time>',
            array_shift($isoSpec),
            $human
        );
    }

    /**
     * @param TimePoint $time
     * @param TimePoint|null $from
     *
     * @return string
     */
    private function relative(TimePoint $time, TimePoint $from = null)
    {
        if (!$from) {
            $from = $this->clock->read();
        }

        $interval = $time->diff($from);
        $days = $interval->format('%a');
        $hours = $interval->format('%h');

        // > 6 months
        if ($days > 180) {
            return $time->format('M j, Y', $this->timezone);

        // 2 weeks - 6 months
        } elseif ($days > 14) {
            return $time->format('F j', $this->timezone);

        // // 3 day - 1 week
        } elseif ($days > 3) {
            return $time->format('M j, g:i A', $this->timezone);

        // // 8 hrs - 72 hrs
        } else if ($hours > 8) {
            if ($time->format('l', 'UTC') === $from->format('l', 'UTC')) {
                return $time->format('g:i A', $this->timezone);
            } else {
                return $time->format('l, g:i A', $this->timezone);
            }

        // // 4 hrs - 8 hrs
        } else if ($hours > 4) {
            return sprintf('%d hours ago', $hours);

        // // 1 hr - 4 hr
        } else if ($hours > 1) {

            $human = sprintf('%d hours', $hours);
            if ($minutes = $interval->format('%i')) {
                $human .= sprintf(', %d minutes' , $minutes);
            }

            return sprintf('%s ago', $human);
        }

        // less than 1 hr, seconds
        $human = sprintf('%d seconds', $interval->format('%s'));
        if ($minutes = $interval->format('%i')) {
            $human = sprintf('%d minutes, ' , $minutes) . $human;
        }

        return sprintf('%s ago', $human);
    }
}
