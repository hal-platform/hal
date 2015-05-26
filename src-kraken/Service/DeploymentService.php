<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Service;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Crypto\CryptoException;
use QL\Hal\Core\Crypto\SymmetricDecrypter;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Target;
use QL\Panthor\Utility\Json;

class DeploymentService
{
    const SUCCESS = 'Configuration successfully deployed to %s';

    const ERR_CONSUL_CONNECTION_FAILURE = 'Update failed. Consul could not be contacted.';
    const ERR_THIS_IS_SUPER_BAD = 'A serious error has occured. Consul was partially updated.';
    const ERR_CONSUL_FAILURE = 'Errors occured while updating Consul. No updates were made.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type SymmetricDecrypter
     */
    private $decrypter;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param EntityManagerInterface $em
     * @param ConsulService $consul
     * @param SymmetricDecrypter $decrypter
     * @param Json $json
     */
    public function __construct(
        EntityManagerInterface $em,
        ConsulService $consul,
        SymmetricDecrypter $decrypter,
        Json $json
    ) {
        $this->em = $em;
        $this->consul = $consul;
        $this->decrypter = $decrypter;

        $this->json = $json;
    }

    /**
     * @param Target $target
     * @param Configuration configuration
     * @param array properties
     *
     * @throws ConsulConnectionException
     *
     * @return bool|null
     */
    public function deploy(Target $target, Configuration $configuration, array $properties)
    {
        // 1.. Encrypt it
        $encrypted = $this->encryptProperties($properties);

        // 2. Save to Consul
        $updates = $this->consul->syncConfiguration($target, $encrypted);

        // 3. Save DB
        $this->saveProperties($configuration, $properties, $updates);

        // 5. Analyze response types
        return $this->handleResponses($target, $configuration, $updates);
    }

    /**
     * Pass in an array of denormalized properties. They will be encrypted, base64 and returned in an assoc array.
     * The checksum will be added to the Property.
     *
     * Example input:
     *     test.key: ConfigurationProperty
     *     test.key2: ConfigurationProperty
     *
     *
     * Example ouput:
     *     test.key: 'base64_and_encrypted'
     *     test.key2: 'base64_and_encrypted'
     *
     * @param ConfigurationProperty[] $properties
     *
     * @return string[]
     */
    private function encryptProperties(array $properties)
    {
        $encrypted = [];

        foreach ($properties as $prop) {

            // @todo actually encrypt
            $key = $prop->key();
            $encrypt = $prop->value();

            // encode
            $encoded = base64_encode($encrypt);

            // save checksum
            $prop->withChecksum(sha1($encoded));

            $encrypted[$key] = $encoded;
        }

        return $encrypted;
    }

    /**
     * @param Configuration $configuration
     * @param ConfigurationProperty[] $properties
     * @param ConsulResponse[] $updates
     *
     * @return void
     */
    private function saveProperties(Configuration $configuration, array $properties, array $updates)
    {
        $configuration->withAudit($this->json->encode($updates));
        $this->em->persist($configuration);

        foreach ($properties as $prop) {
            $this->em->persist($prop);
        }

        $this->em->flush();
    }

    /**
     * @param Target $target
     * @param Configuration $configuration
     * @param ConsulResponse[] $responses
     *
     * @return bool|null
     */
    private function handleResponses(Target $target, Configuration $configuration, array $responses)
    {
        try {
            $success = $this->parseConsulResponses($responses);
        } catch (MixedUpdateException $ex) {
            $success = null;

        } finally {

            $configuration
                ->withAudit($this->json->encode($responses))
                ->withIsSuccess($success);

            $target->withConfiguration($configuration);

            $this->em->persist($configuration);
            $this->em->persist($target);

            $this->em->flush();
        }

        return $success;
    }

    /**
     * @param ConsulResponse[] $responses
     *
     * @throws MixedUpdateException
     *
     * @return bool
     */
    private function parseConsulResponses(array $responses)
    {
        // Nothing was there, and nothing was updated. Success!
        if (count($responses) === 0) {
            return true;
        }

        $hasSuccesses = $hasFailures = false;
        foreach ($responses as $update) {
            $hasSuccesses = $hasSuccesses || $update->isSuccess();
            $hasFailures = $hasFailures || !$update->isSuccess();
        }

        // All Success!
        if ($hasSuccesses && !$hasFailures) {
            return true;
        }

        // All failures
        if (!$hasSuccesses && $hasFailures) {
            return false;
        }

        // mixed updated. This is super bad.
        throw new MixedUpdateException(self::ERR_THIS_IS_SUPER_BAD);
    }

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    private function decrypt($encrypted)
    {
        try {
            $value = $this->decrypter->decrypt($encrypted);
        } catch (CryptoException $ex) {
            $value = null;
        }

        return $value;
    }
}
