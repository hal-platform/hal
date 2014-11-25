<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use InvalidArgumentException;

class Flash
{
    const INFO = 'info';
    const ERROR = 'error';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    /**
     * @type string
     */
    private $message;

    /**
     * @type string
     */
    private $type;

    private static $validTypes = [
        self::INFO,
        self::ERROR,
        self::SUCCESS,
        self::WARNING
    ];

    /**
     * @param string $message
     * @param string $type
     */
    public function __construct($message, $type = self::INFO)
    {
        $this->message = $message;
        $this->type = $type;

        if (!in_array($this->type, self::$validTypes)) {
            throw new InvalidArgumentException(sprintf('Invalid type given: %s', $this->type));
        }
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->message();
    }
}
