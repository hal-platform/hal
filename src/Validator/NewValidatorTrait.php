<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

trait NewValidatorTrait
{
    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateIsRequired($value): bool
    {
        if (!mb_strlen($value, 'UTF-8') > 0) {
            return false;
        }

        return true;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateSanityCheck($value): bool
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            return false;
        }

        return true;
    }

    /**
     * @param string $value
     * @param int $min
     * @param int $max
     *
     * @return bool
     */
    private function validateLength($value, $min, $max): bool
    {
        if (mb_strlen($value, 'UTF-8') < $min) {
            return false;
        }

        if (mb_strlen($value, 'UTF-8') > $max) {
            return false;
        }

        return true;
    }

    /**
     * @param string $value
     * @param string $regexCharacterClass
     *
     * @return bool
     */
    private function validateCharacterWhitelist($value, $regexCharacterClass): bool
    {
        if (!preg_match("@^[${regexCharacterClass}]*$@", $value)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $value
     * @param string $regexCharacterClass
     *
     * @return bool
     */
    private function validateCharacterBlacklist($value, $regexCharacterClass): bool
    {
        if (preg_match("@[${regexCharacterClass}]+@", $value)) {
            return false;
        }

        return true;
    }
}
