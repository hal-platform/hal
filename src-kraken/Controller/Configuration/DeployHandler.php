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
use QL\Hal\FlashFire;
use QL\Kraken\ConsulService;
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
    const ERR_CONSUL_FAILURE = 'An error occured while updating Consul.';
    const ERR_JSON_DECODE = 'Invalid property "%s": %s';
    const ERR_DECRYPT = 'Could not decrypt secure property "%s"';

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
     * @type FlashFire
     */
    private $flashFire;

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
     * @param FlashFire $flashFire
     * @param EntityManagerInterface $em
     * @param ConsulService $consul
     * @param SymmetricDecrypter $decrypter
     * @param callable $random
     */
    public function __construct(
        Target $target,
        User $currentUser,
        Json $json,
        FlashFire $flashFire,
        EntityManagerInterface $em,
        ConsulService $consul,
        SymmetricDecrypter $decrypter,
        callable $random
    ) {
        $this->target = $target;
        $this->currentUser = $currentUser;

        $this->json = $json;
        $this->flashFire = $flashFire;
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
        try {
            $json = $this->generateJson($properties);

        } catch (InvalidPropertyException $ex) {
            $json = null;

            // handle error
            $msg = $ex->getMessage();
            $this->flashFire->fire($msg, 'kraken.predeploy', 'error', ['target' => $this->target->id()]);
        }

        // 4. Encrypt it

            # ????

        // 5. Save to DB
        $configuration
            ->withConfiguration($json)
            ->withChecksum(sha1($json));

        $this->em->persist($configuration);
        foreach ($properties as $prop) {
            $this->em->persist($prop);
        }

        $this->em->flush();

        // 6. Save to Consul
        $success = $this->sendToConsul($configuration, $this->target);

        if (!$success) {
            $this->flashFire->fire(self::ERR_CONSUL_FAILURE, 'kraken.deploy', 'error', [
                'target' => $this->target->id()
            ]);
        }

        // 7. Update target to new configuration
        $this->target->withConfiguration($configuration);
        $this->em->persist($this->target);
        $this->em->flush();

        $msg = sprintf(self::SUCCESS, $this->target->environment()->name());
        $this->flashFire->fire($msg, 'kraken.status', 'success', [
            'application' => $this->target->application()->id()
        ]);
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
                ->withSchema($schema)
                ->withUser($this->currentUser);

            $newconfig[$cf->key()] = $cf;
        }

        return $newconfig;
    }

    /**
     * @param ConfigurationProperty[] $properties
     *
     * @return string
     */
    private function generateJson(array $properties)
    {
        $jsonable  = [];

        foreach ($properties as $prop) {

            $value = $prop->value();

            if ($prop->isSecure()) {
                $value = $this->decrypt($value);
                if (!is_string($value)) {
                    $msg = sprintf(self::ERR_DECRYPT, $prop->key());
                    throw new InvalidPropertyException($msg);
                }
            }

            // db values are json, so must be first decoded
            // This decoded/reencode step allows us to offload some validation to the json process
            $value = $this->json->decode($value);

            if ($value === null) {
                $msg = sprintf(self::ERR_JSON_DECODE, $prop->key(), $this->json->lastJsonErrorMessage());
                throw new InvalidPropertyException($msg);
            }

            $jsonable[$prop->key()] = $value;
        }

        $this->json->setEncodingOptions(JSON_PRETTY_PRINT);
        return $this->json->encode($jsonable);
    }

    /**
     * @param Configuration $configuration
     * @param Target $target
     *
     * @return bool
     */
    private function sendToConsul(Configuration $configuration, Target $target)
    {
        return $this->consul->sendConfiguration($configuration, $target);
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
