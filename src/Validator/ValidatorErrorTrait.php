<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

trait ValidatorErrorTrait
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @return void
     */
    private function resetErrors(): void
    {
        $this->errors = [];
    }

    /**
     * @return bool
     */
    private function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * @param string $msg
     * @param string|null $field
     *
     * @return void
     */
    private function addError(string $msg, string $field = null): void
    {
        if (!$field) {
            $field = 'all';
        }

        if (!($this->errors[$field] ?? [])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $msg;
    }

    /**
     * @return array
     */
    public function errors(): array
    {
        if ($this->errors === null) {
            $this->resetErrors();
        }

        return $this->errors;
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function errorsFor($field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * @param string $name
     * @param string|null $field
     *
     * @return void
     */
    private function addRequiredError($name, $field = null): void
    {
        $msg = sprintf('%s is required', $name);
        $this->addError($msg, $field);
    }

    /**
     * @param string $name
     * @param int $min
     * @param int $max
     * @param string|null $field
     *
     * @return void
     */
    private function addLengthError($name, $min, $max, $field = null): void
    {
        $isMinZero = ($min === 0);
        $isMaxBig = ($max > 200);

        if ($isMinZero) {
            $msg = sprintf('%s must be %d characters or fewer', $name, $max);

        } elseif ($isMaxBig) {
            $msg = sprintf('%s must be %d characters or more', $name, $min);

        } else {
            $msg = sprintf('%s must be contain %d - %d characters', $name, $min, $max);
        }

        $this->addError($msg, $field);
    }
}
