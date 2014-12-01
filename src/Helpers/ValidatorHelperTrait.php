<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

trait ValidatorHelperTrait
{
    /**
     * @param string $value
     * @param string $friendlyName
     * @param int $length
     * @param bool $isRequired
     *
     * @return array
     */
    private function validateSimple($value, $friendlyName, $length, $isRequired = false)
    {
        $errors = [];

        if (!$value && !$isRequired) {
            return $errors;
        }

        if (!$value) {
            $errors[] = sprintf('%s is required', $friendlyName);
        }

        if (!preg_match('@^[a-z0-9-.]*$@', $value)) {
            $errors[] = sprintf('%s must be be composed of lowercase alphanumeric, hyphen, and period characters', $friendlyName);
        }

        if (mb_strlen($value, 'UTF-8') > $length) {
            $errors[] = sprintf('%s must be %d characters or fewer', $friendlyName, $length);
        }

        if (mb_strlen($value, 'UTF-8') < 2) {
            $errors[] = sprintf('%s must be more than one character', $friendlyName);
        }

        return $errors;
    }

    /**
     * @param string $value
     * @param string $friendlyName
     * @param int $length
     * @param boolean $isRequired
     *
     * @return array
     */
    private function validateText($value, $friendlyName, $length, $isRequired = false)
    {
        $errors = [];

        if (!$value && !$isRequired) {
            return $errors;
        }

        if (!$value) {
            $errors[] = sprintf('%s is required', $friendlyName);
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $errors[] = sprintf('%s must be valid UTF-8', $friendlyName);
        }

        if (mb_strlen($value, 'UTF-8') > $length) {
            $errors[] = sprintf('%s must be %d characters or fewer', $friendlyName, $length);
        }

        return $errors;
    }
}