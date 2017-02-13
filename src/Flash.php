<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI;

use InvalidArgumentException;

class Flash
{
    const INFO = 'info';
    const ERROR = 'error';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $details;

    private static $validTypes = [
        self::INFO,
        self::ERROR,
        self::SUCCESS,
        self::WARNING
    ];

    /**
     * @param string $message
     * @param string $type
     * @param string $details
     */
    public function __construct($message, $type = self::INFO, $details = '')
    {
        $this->message = $message;
        $this->type = $type;
        $this->details = $details;

        if (!in_array($this->type, self::$validTypes)) {
            throw new InvalidArgumentException(sprintf('Invalid type given: %s', $this->type));
        }
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
    public function message()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function details()
    {
        return $this->details;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->message();
    }
}
