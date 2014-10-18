<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use InvalidArgumentException;

/**
 * Unique Helper
 *
 * Generate random build IDs
 *
 * @todo move to hal-core?
 */
class UniqueHelper
{
    const BASE58 = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';

    const BASE58_3CHAR = 3364;
    const BASE58_4CHAR = 195112;
    const BASE58_5CHAR = 11316496;
    const BASE58_6CHAR = 656356768;

    /**
     * @type string
     */
    private $version;

    /**
     * @type array
     */
    private $alphabet;

    /**
     * @type int
     */
    private $base;

    /**
     * @param string $version
     * @param string $alphabet
     */
    public function __construct($version, $alphabet)
    {
        $this->version = $version;

        $this->alphabet = str_split($alphabet);
        $this->base = count($this->alphabet);

        if ($this->base < 2) {
            throw new InvalidArgumentException('You must use an alphabet with more than 1 character.');
        }
    }

    /**
     * @return string
     */
    public function generateBuildId()
    {
        $date = $this->timeHash();
        $unique = $this->randomHash(4);

        // bVERSION.XXXYYYY
        return sprintf(
            'b%d.%s%s',
            $this->version,
            $this->encode($date),
            $this->encode($unique)
        );
    }

    /**
     * @return string
     */
    public function generatePushId()
    {
        $date = $this->timeHash();
        $unique = $this->randomHash(4);

        // p{VERSION}.{TIME}{UNIQUE}
        return sprintf(
            'p%d.%s%s',
            $this->version,
            $this->encode($date),
            $this->encode($unique)
        );
    }

    /**
     * Encode a number to an arbitrary base.
     *
     * @param int $num
     * @return string
     */
    public function encode($num)
    {
        $encoded = '';
        while($num > 0) {
            $encoded = $this->alphabet[$num % $this->base] . $encoded;
            $num = floor($num / $this->base);
        }

        return $encoded;
    }

    /**
     * Get a random number that will hash to a certain size in base 58.
     *
     * How a base10 int will hash to base58:
     *
     *      3364 - min 3 char
     *    195112 - min 4 char
     *  11316496 - min 5 char
     * 656356768 - min 6 char
     *
     * 3 char =     191 748 possibilities
     * 4 char =  11 121 384 possibilities
     * 5 char = 645 040 272 possibilities
     *
     * @param int $numChars
     * @return int
     */
    protected function randomHash($numChars)
    {
        if ($numChars == 3) {
            return mt_rand(self::BASE58_3CHAR, self::BASE58_4CHAR - 1);

        } elseif ($numChars == 4) {
            return mt_rand(self::BASE58_4CHAR, self::BASE58_5CHAR - 1);

        } elseif ($numChars == 5) {
            return mt_rand(self::BASE58_5CHAR, self::BASE58_6CHAR - 1);

        } elseif ($numChars == 6) {
            return mt_rand(self::BASE58_6CHAR, mt_getrandmax());
        }

        return mt_rand(0, mt_getrandmax());
    }

    /**
     * Get a number based on year and date.
     *
     * For a consistent prefix that increments and can be used to easily find builds from a certain time set.
     *
     * The 2 digit year is used so that every time > 2010ish will consistently hash to 3 characters in base 58.
     *
     * What happens in the year 2100? I don't care. I will be dead. <-- Planned obsolescence
     */
    protected function timeHash()
    {
        $day = date('y') . str_pad(date('z'), 3, '0', STR_PAD_LEFT);
        return (int) $day;
    }
}
