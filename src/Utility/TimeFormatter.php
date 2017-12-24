<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Utility;

use DateTime;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;

class TimeFormatter
{
    const OUTPUT_FORMAT_DATE = 'M j, Y g:i A';

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var string
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
            $human = sprintf('%d minutes, ', $minutes) . $human;
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
        $minutes = $interval->format('%i');
        $seconds = $interval->format('%s');

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
                return $time->format('D, g:i A', $this->timezone);
            }

        // // 4 hrs - 8 hrs
        } else if ($hours > 4) {
            return sprintf('%d hr ago', $hours);

        // // 1 hr - 4 hr
        } else if ($hours > 1) {
            return sprintf('%d hr, %d min ago', $hours, $minutes);

        // 10 min - 1 hr
        } else if ($minutes > 10) {
            return sprintf('%d min ago', $minutes);

        // 1 min - 10 min
        } else if ($minutes > 1) {
            return sprintf('%d min, %d sec ago', $minutes, $seconds);

        // 0 - 1 min
        } else if ($seconds > 0) {
            return sprintf('%d sec ago', $seconds);
        }

        // To the future!
        return $time->format('g:i A', $this->timezone);
    }
}
