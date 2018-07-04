<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Job;
use Hal\Core\Entity\Job\JobMeta;
use QL\MCP\Common\Clock;

class MetaValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const ERR_INVALID_NAME = 'Metadata name is invalid. Please use only alphanumeric characters and underscore (_) or period (.)';
    private const ERR_INVALID_DATA = 'Data is invalid. Please provide text data less than 20KB.';

    private const VALID_DATA_BYTE_MAX = 20000;
    private const VALID_NAME_REGEX = '/^[a-zA-Z]{1}[a-zA-Z0-9\_\.]{0,99}$/';

    /**
     * @param Job $job
     * @param mixed $name
     * @param mixed $data
     *
     * @return JobMeta|null
     */
    public function isValid(Job $job, $name, $data): ?JobMeta
    {
        $this->resetErrors();

        $name = preg_replace('/[^a-zA-Z0-9\_\.]/', '_', strtolower($name));

        $this->validateName($name);
        $this->validateData($data);

        if ($this->hasErrors()) {
            return null;
        }

        $value = trim($data);

        $meta = (new JobMeta)
            ->withName($name)
            ->withValue($data)
            ->withJob($job);

        $job->meta()->add($meta);

        return $meta;
    }

    /**
     * @param Job $job
     * @param array $metadatas
     *
     * @return array|null
     */
    public function isBulkValid(Job $job, array $metadatas): ?array
    {
        $errors = $metas = [];

        foreach ($metadatas as $name => $value) {
            if ($meta = $this->isValid($job, $name, $value)) {
                $metas[] = $meta;
            } else {
                $errors = array_merge_recursive($errors, $this->errors());
            }
        }

        if ($errors) {
            $this->importErrors($errors);
            return null;
        }

        return $metas;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateRegex($name, self::VALID_NAME_REGEX)) {
            $this->addError(self::ERR_INVALID_NAME);
        }
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    private function validateData($data)
    {
        if (!is_string($data) || strlen($data) === 0) {
            $this->addError(self::ERR_INVALID_DATA);
        }

        if (strlen($data) > self::VALID_DATA_BYTE_MAX) {
            $this->addError(self::ERR_INVALID_DATA);
        }
    }
}
