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

        return sprintf(
            '<time datetime="%s">%s</time>',
            $time->format(DateTime::RFC3339, 'UTC'),
            $time->format(self::OUTPUT_FORMAT_DATE, $this->timezone)
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

}
