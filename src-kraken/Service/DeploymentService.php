<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Service;

use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use MCP\Crypto\Exception\CryptoException;
use MCP\Crypto\Package\QuickenMessagePackage;
use MCP\Crypto\Package\TamperResistantPackage;
use MCP\QKS\QKSException;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Service\Exception\ConfigurationException;
use QL\Kraken\Service\Exception\DecryptionException;
use QL\Kraken\Service\Exception\MixedUpdateException;
use QL\Kraken\Service\Exception\QKSConnectionException;
use QL\Kraken\Utility\CryptoFactory;
use QL\Panthor\Utility\Json;

class DeploymentService
{
    const SUCCESS = 'Configuration successfully deployed to %s';

    const ERR_QKS_ERROR = 'An error occured while contacting QKS.';
    const ERR_QKS_KEY_NOT_CONFIGURED = 'QKS encryption key is not configured for this environment.';
    const ERR_THIS_IS_SUPER_BAD = 'A serious error has occured. Consul was partially updated.';
    const ERR_DECRYPT_FAILURE = 'Secure property "%s" could not be decrypted.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type TamperResistantPackage
     */
    private $encryption;

    /**
     * @type CryptoFactory
     */
    private $cryptoFactory;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param EntityManagerInterface $em
     * @param ConsulService $consul
     * @param TamperResistantPackage $encryption
     * @param CryptoFactory $cryptoFactory
     * @param Json $json
     */
    public function __construct(
        EntityManagerInterface $em,
        ConsulService $consul,
        TamperResistantPackage $encryption,
        CryptoFactory $cryptoFactory,
        Json $json
    ) {
        $this->em = $em;
        $this->consul = $consul;
        $this->encryption = $encryption;
        $this->cryptoFactory = $cryptoFactory;

        $this->json = $json;
    }

    /**
     * @param Target $target
     * @param Configuration configuration
     * @param array properties
     *
     * @throws ServiceException
     *
     * @return bool|null
     */
    public function deploy(Target $target, Configuration $configuration, array $properties)
    {
        // 0. Get QMP
        $qmp = $this->getQMP($target);

        // 1. Encrypt with QMP and QKS
        $encrypted = $this->encryptProperties($qmp, $target->key(), $properties);

        // 2. Save to Consul
        $updates = $this->consul->syncConfiguration($target, $encrypted);

        // 3. Save DB
        $this->saveProperties($configuration, $properties, $updates);

        // 5. Analyze response types
        return $this->handleResponses($target, $configuration, $updates);
    }

    /**
     * @param Target $target
     *
     * @throws ConfigurationException
     *
     * @return QuickenMessagePackage
     */
    private function getQMP(Target $target)
    {
        if (!$target->key()) {
            throw new ConfigurationException(self::ERR_QKS_KEY_NOT_CONFIGURED);
        }

        return $this->cryptoFactory->getQMP($target->environment());
    }

    /**
     * Pass in an array of denormalized properties. They will be encrypted, base64 and returned in an assoc array.
     * The checksum will be added to the Property.
     *
     * Example input:
     *     test.key: Snapshot
     *     test.key2: Snapshot
     *
     *
     * Example ouput:
     *     test.key: 'base64_and_encrypted'
     *     test.key2: 'base64_and_encrypted'
     *
     * @param QuickenMessagePackage $qmp
     * @param string $recepient
     * @param Snapshot[] $properties
     *
     * @throws DecryptionException
     * @throws QKSConnectionException
     *
     * @return string[]
     */
    private function encryptProperties(QuickenMessagePackage $qmp, $recepient, array $properties)
    {
        $encrypteds = [];

        foreach ($properties as $prop) {

            $key = $prop->key();

            $value = $prop->value();
            if ($prop->isSecure()) {
                if (null === ($value = $this->decrypt($value))) {
                    throw new DecryptionException(sprintf(self::ERR_DECRYPT_FAILURE, $key));
                }
            }

            $encrypted = $this->encrypt($qmp, $recepient, $value);

            // encode
            $encoded = base64_encode($encrypted);

            // save checksum
            $prop->withChecksum(sha1($encoded));

            $encrypteds[$key] = $encoded;
        }

        return $encrypteds;
    }

    /**
     * @param Configuration $configuration
     * @param Snapshot[] $properties
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
     * @param QuickenMessagePackage $qmp
     * @param string $receipientKey
     * @param string $decrypted
     *
     * @throws QKSConnectionException
     *
     * @return string|null
     */
    private function encrypt(QuickenMessagePackage $qmp, $receipientKey, $decrypted)
    {
        $receipients = [
            $receipientKey
        ];

        try {
            $encrypted = $qmp->encrypt($decrypted, $receipients);
        } catch (QKSException $ex) {
            throw new QKSConnectionException(self::ERR_QKS_ERROR);
        } catch (CryptoException $ex) {
            throw new QKSConnectionException($ex->getMessage());
        }

        return $encrypted;
    }

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    private function decrypt($encrypted)
    {
        if (!$encrypted) {
            return '';
        }

        try {
            $decrypted = $this->encryption->decrypt($encrypted);
        } catch (CryptoException $ex) {
            $decrypted = null;
        }

        return $decrypted;
    }
}
