<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Crypto\SymmetricEncrypter;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;

class PropertyValidator
{
    const ERR_VALUE_REQUIRED = 'Please enter a value.';
    const ERR_MISSING_SCHEMA = 'Please select a property.';
    const ERR_DUPLICATE_PROPERTY = 'This property is already set for this environment.';
    const ERR_TOO_BIG = 'This value is too large. Properties stored in Kraken must be smaller than %skb.';

    const ERR_INTEGER = 'Please enter a valid integer number.';
    const ERR_FLOAT = 'Please enter a valid number (must include decimal).';
    const ERR_BOOLEAN = 'Please enter a valid boolean flag value.';
    const ERR_LIST = 'Please enter a list of values.';

    /**
     * Technically this is the max size storeable by Consul (512kb) after being json encoded, encrypted, and base64ed.
     */
    const MAX_VALUE_SIZE_BYTES_CONSUL = 330000;

    /**
     * Artifically limit the value of each value to 20k.
     *
     * The encrypted values are about 50% efficient (binary->hexed), and the column type is 64kbytes.
     */
    const MAX_VALUE_SIZE_BYTES = 20000;

    /**
     * @type EntityRepository
     */
    private $schemaRepo;
    private $propertyRepo;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type SymmetricEncrypter
     */
    private $encrypter;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param Json $json
     * @param SymmetricEncrypter $encrypter
     * @param callable $random
     */
    public function __construct(
        EntityManagerInterface $em,
        Json $json,
        SymmetricEncrypter $encrypter,
        callable $random
    ) {
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
        $this->propertyRepo = $em->getRepository(Property::CLASS);

        $this->json = $json;
        $this->encrypter = $encrypter;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @param Environment $environment
     * @param Request $request
     * @param string $schemaId
     *
     * @return Property|null
     */
    public function isValid(Environment $environment, Request $request, $schemaId)
    {
        $this->errors = [];

        if (!$schema = $this->schemaRepo->find($schemaId)) {
            $this->errors[] = self::ERR_MISSING_SCHEMA;
        }

        if ($this->errors) return null; // bomb

        $value = $this->resolvePropertyValueFromRequest($request, $schema);

        $value = $this->validateValue($schema, $value);

        if ($this->errors) return null; // bomb

        // dupe check
        if ($dupe = $this->propertyRepo->findOneBy(['schema' => $schema, 'environment' => $environment])) {
            $this->errors[] = self::ERR_DUPLICATE_PROPERTY;
        }

        if ($this->errors) return null; // bomb

        $id = call_user_func($this->random);
        $encoded = $this->encode($schema, $value);

        return (new Property)
            ->withId($id)
            ->withValue($encoded)
            ->withSchema($schema)
            ->withApplication($schema->application())
            ->withEnvironment($environment);
    }

    /**
     * @param Property $property
     * @param string|string[] $value
     *
     * @return Property|null
     */
    public function isEditValid(Property $property, $value)
    {
        $this->errors = [];

        $value = $this->validateValue($property->schema(), $value);

        if ($this->errors) return null; // bomb

        // update property
        $encoded = $this->encode($property->schema(), $value);
        $property->withValue($encoded);

        return $property;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param Request $request
     * @param Schema $schema
     *
     * @return string|string[]
     */
    public function resolvePropertyValueFromRequest(Request $request, Schema $schema)
    {
        $value = $request->post('value');

        // get explicit input if generic was not passed (explicit will be removed by javascript)
        if ($value === null) {
            $formField = sprintf('value_%s', $schema->dataType());
            $value = $request->post($formField);
        }

        return $value;
    }

    /**
     * Validate and sanitize value
     *
     * @param Schema $schema
     * @param string $value
     *
     * @return string|string[]
     */
    private function validateValue(Schema $schema, $value)
    {
        if ($schema->dataType() === 'integer') {
            $value = str_replace(',', '', $value);

            if (preg_match('/^[\-]?[\d]+$/', $value) !== 1) {
                $this->errors[] = self::ERR_INTEGER;
            }

        } elseif ($schema->dataType() === 'float') {
            $value = str_replace(',', '', $value);

            if (preg_match('/^[\-]?[\d]+[\.][\d]+$/', $value) !== 1) {
                $this->errors[] = self::ERR_FLOAT;
            }

        } elseif ($schema->dataType() === 'bool') {
            if (!in_array($value, ['true', 'false'], true)) {
                $this->errors[] = self::ERR_BOOLEAN;
            }

        } elseif ($schema->dataType() === 'strings') {
            if (!is_array($value)) {
                $this->errors[] = self::ERR_LIST;
            }

        } else {
            // "string"
        }

        $size = strlen($this->json->encode($value));
        if (strlen($size) > self::MAX_VALUE_SIZE_BYTES) {
            $this->errors[] = sprintf(self::ERR_TOO_BIG, (self::MAX_VALUE_SIZE_BYTES/1000));
        }

        return $value;
    }

    /**
     * @param Schema $schema
     * @param string $value
     *
     * @return string
     */
    private function encode(Schema $schema, $value)
    {
        if ($schema->dataType() === 'integer') {
            $value = (int) $value;

        } elseif ($schema->dataType() === 'float') {
            $value = (float) $value;

        } elseif ($schema->dataType() === 'bool') {
            if (!is_bool($value)) {
                $value = ($value === 'true') ? true : false;
            }

        } elseif ($schema->dataType() === 'strings') {
            foreach ($value as &$listValue) {
                $listValue = (string) $listValue;
            }

        } else {
            // "string"
            $value = (string) $value;
        }

        // This is where the magic happens. This is the exact value stored in the DB, and eventually encrypted/base64 and sent to consul.
        $encoded = $this->encodeValue($value);

        if ($schema->isSecure()) {
            $encoded = $this->encrypter->encrypt($encoded);
        }

        return $encoded;
    }

    /**
     * @see https://wiki.php.net/rfc/json_preserve_fractional_part
     * To properly encode floats without data loss, this requires PHP >= 5.6.6 and the JSON_PRESERVE_ZERO_FRACTION flag.
     *
     * @param mixed $value
     * @return string
     */
    private function encodeValue($value)
    {
        $encoded = $this->json->encode($value);

        return $encoded;
    }

    /**
     * Not used at runtime, was just used to get static max size.
     *
     * @return int
     */
    private function getMaxSizeInBytes()
    {
        $maxBytes = 512000;
        $receipients = 2;
        $sodiumPadding = ($receipients * 56) + 34;

        // Reverse base64
        $debase64 = ($maxBytes * 3) / 4;

        // Encryption padding
        $decrypted = $debase64 - $sodiumPadding;

        // Fudge it a bit for json encoding and whatnot
        $jsonFudge = $decrypted * .9;

        return floor($jsonFudge * 1000);
    }
}
