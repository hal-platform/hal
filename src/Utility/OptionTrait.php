<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Utility;

trait OptionTrait
{
    /**
     * @var int
     */
    private $flags;

    /**
     * @param string|int $flag
     *
     * @return void
     */
    public function withFlag($flag): void
    {
        $flag = $this->parseFlag($flag);

        $this->flags = $this->flags | $flag;
    }

    /**
     * @param string|int $flag
     *
     * @return void
     */
    public function withoutFlag($flag): void
    {
        $flag = $this->parseFlag($flag);

        $this->flags = $this->flags & ~$flag;
    }

    /**
     * @param int $flag
     *
     * @return bool
     */
    private function isFlagEnabled($flag): bool
    {
        $flag = $this->parseFlag($flag);

        return $flag === ($this->flags & $flag);
    }

    /**
     * @param string|int $flag
     *
     * @return int
     */
    private function parseFlag($flag)
    {
        if (!isset($this->flags)) {
            $this->flags = 0;
        }

        if (is_string($flag) && defined("static:${flag}")) {
            $flag = constant("static:${flag}");
        }

        if (!is_int($flag)) {
            return 0;
        }

        return $flag;
    }

}
