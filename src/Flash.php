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
    public const INFO = 'info';
    public const ERROR = 'error';
    public const SUCCESS = 'success';
    public const WARNING = 'warning';

    private const VALID_FLASH_TYPES = [
        self::INFO,
        self::ERROR,
        self::SUCCESS,
        self::WARNING
    ];

    private const ERRT_FLASH = 'Invalid flash type "%s" specified.';

    /**
     * @var array
     */
    private $messages;
    private $original;

    /**
     * @param array $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = [];

        foreach ($messages as $msg) {
            if (!is_array($msg)) {
                continue;
            }

            if (!array_key_exists('type', $msg) || !array_key_exists('message', $msg)) {
                continue;
            }

            $this->withMessage($msg['type'], $msg['message'], $msg['details'] ?? '');
        }

        $this->original = $this->messages;
    }

    /**
     * @param string $data
     *
     * @return Flash|null
     */
    public static function fromCookie($data)
    {
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return null;
        }

        return new self($decoded);
    }

    /**
     * @param Flash $flash
     *
     * @return string
     */
    public static function toCookie(Flash $flash)
    {
        return json_encode($flash->getMessages(), JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @param string $type
     * @param string $message
     * @param string $details
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public function withMessage(string $type, string $message, string $details = ''): Flash
    {
        if (!in_array($type, self::VALID_FLASH_TYPES)) {
            throw new InvalidArgumentException(sprintf(self::ERRT_FLASH, $type));
        }

        $this->messages[] = [
            'type' => $type,
            'message' => $message,
            'details' => $details
        ];

        return $this;
    }

    /**
     * Get all messages.
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Remove all messages.
     *
     * @return void
     */
    public function removeMessages(): void
    {
        $this->messages = [];
    }

    /**
     * Get all messages and flush them.
     *
     * @return array
     */
    public function flush(): array
    {
        $messages = $this->getMessages();

        $this->removeMessages();

        return $messages;
    }

    /**
     * @return bool
     */
    public function hasMessages(): bool
    {
        return count($this->messages) > 0;
    }

    /**
     * @return bool
     */
    public function hasChanged(): bool
    {
        return $this->messages !== $this->original;
    }
}
