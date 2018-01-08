<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

trait ValidatorTrait
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
     * @param array $options
     *
     * @return bool
     */
    private function validateIn($value, array $options): bool
    {
        return in_array($value, $options, true);
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
        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min) {
            return false;
        }

        if ($length > $max) {
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
