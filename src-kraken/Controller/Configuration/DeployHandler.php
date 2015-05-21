<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Crypto\CryptoException;
use QL\Hal\Core\Crypto\SymmetricDecrypter;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\Service\ConsulService;
use QL\Kraken\Service\MixedUpdateException;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\Json;

class DeployHandler implements ControllerInterface
{
    const SUCCESS = 'Configuration successfully deployed to %s';
    const ERR_JSON_DECODE = 'Invalid property "%s": %s';
    const ERR_DECRYPT = 'Could not decrypt secure property "%s"';

    const ERR_CONSUL_CONNECTION_FAILURE = 'Update failed. Consul could not be contacted.';
    const ERR_THIS_IS_SUPER_BAD = 'A serious error has occured. Consul was partially updated.';
    const ERR_CONSUL_FAILURE = 'Errors occured while updating Consul. No updates were made.';

    /**
     * @type Target
     */
    private $target;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type ConsulService
     */
    private $consul;

    /**
     * @type SymmetricDecrypter
     */
    private $decrypter;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;

    /**
     * @type callable
     */
    private $random;

    /**
     * @param Target $target
     * @param User $currentUser
     * @param Json $json
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param ConsulService $consul
     * @param SymmetricDecrypter $decrypter
     * @param callable $random
     */
    public function __construct(
        Target $target,
        User $currentUser,
        Json $json,
        Flasher $flasher,
        EntityManagerInterface $em,
        ConsulService $consul,
        SymmetricDecrypter $decrypter,
        callable $random
    ) {
        $this->target = $target;
        $this->currentUser = $currentUser;

        $this->json = $json;
        $this->flasher = $flasher;
        $this->consul = $consul;
        $this->decrypter = $decrypter;
        $this->random = $random;

        $this->em = $em;
        $this->propertyRepo = $this->em->getRepository(Property::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        // 1. Create a configuration for this environment
        $configuration = $this->buildConfiguration($this->target->application(), $this->target->environment());

        // 2. Get all property/schema pairs for environment
        $properties = $this->buildProperties($configuration);

        // 3. Generate a json payload with these values
        // ???

        // 4. Encrypt it
        $encrypted = $this->encryptProperties($properties);

        // 5. Save to DB. Just in case consul blows up
        $this->saveProperties($configuration, $properties);

        // 6. Save to Consul
        $updates = $this->consul->syncConfiguration($this->target, $encrypted);

        // Connection error to consul
        $this->handleConnectionFailure($configuration, $updates);

        // Analyze other response types
        $status = $this->handleResponses($configuration, $updates);

        // And finally, go away.
        $this->redirect($status);
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @return Configuration
     */
    private function buildConfiguration(Application $application, Environment $environment)
    {
        $id = call_user_func($this->random);
        $config = (new Configuration)
            ->withId($id)
            ->withApplication($application)
            ->withEnvironment($environment)
            ->withUser($this->currentUser);

        return $config;
    }

    /**
     * @param Configuration $configuration
     *
     * @return ConfigurationProperty[]
     */
    private function buildProperties(Configuration $configuration)
    {
        $newconfig = [];

        $properties = $this->propertyRepo->findBy([
            'application' => $configuration->application(),
            'environment' => $configuration->environment()
        ]);

        foreach ($properties as $property) {
            $schema = $property->schema();

            $id = call_user_func($this->random);

            $cf = (new ConfigurationProperty)
                ->withId($id)
                ->withKey($schema->key())
                ->withDataType($schema->dataType())
                ->withIsSecure($schema->isSecure())
                ->withValue($property->value())

                ->withConfiguration($configuration)
                ->withProperty($property)
                ->withSchema($schema);

            $newconfig[$cf->key()] = $cf;
        }

        return $newconfig;
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
     *
     * @return void
     */
    private function saveProperties(Configuration $configuration, array $properties)
    {
        $this->em->persist($configuration);

        foreach ($properties as $prop) {
            $this->em->persist($prop);
        }

        $this->em->flush();
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
     * @param Configuration $configuration
     * @param ConsulResponse[] $responses
     *
     * @throws Stop Exception
     *
     * @return void
     */
    private function handleConnectionFailure(Configuration $configuration, array $responses)
    {
        if ($responses !== null) {
            return;
        }

        $configuration->withAudit($this->json->encode($responses));
        $this->em->persist($configuration);
        $this->em->flush();

        $this->flasher
            ->withFlash(self::ERR_CONSUL_CONNECTION_FAILURE, 'error')
            ->load('kraken.deploy', ['target' => $this->target->id()]);
    }

    /**
     * @param Configuration $configuration
     * @param ConsulResponse[] $responses
     *
     * @return bool|null
     */
    private function handleResponses(Configuration $configuration, array $responses)
    {
        try {
            $success = $this->parseConsulResponses($responses);
        } catch (MixedUpdateException $ex) {
            $success = null;

        } finally {

            $configuration
                ->withAudit($this->json->encode($responses))
                ->withIsSuccess($success);

            $this->target->withConfiguration($configuration);

            $this->em->persist($configuration);
            $this->em->persist($this->target);

            $this->em->flush();
        }

        return $success;
    }

    /**
     * @param bool|null $status
     *
     * @throws Stop Exception
     *
     * @return void
     */
    private function redirect($status)
    {
        if ($status === null) {
            // Mixed update. BAD!
            $this->flasher->withFlash(self::ERR_THIS_IS_SUPER_BAD, 'error');

        } elseif (!$status) {
            // True failure.
            $this->flasher->withFlash(self::ERR_CONSUL_FAILURE, 'error');

        } else {
            // Success
            $this->flasher->withFlash(sprintf(self::SUCCESS, $this->target->environment()->name()), 'success');
        }

        // byebye
        $this->flasher->load('kraken.status', ['application' => $this->target->application()->id()]);
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
