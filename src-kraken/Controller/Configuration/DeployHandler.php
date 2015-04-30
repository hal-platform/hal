<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\GUID;
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
    const ERR_JSON_DECODE = 'Invalid Property "%s": %s';

    /**
     * @type Target
     */
    private $target;

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
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $propRepository;

    /**
     * @param Target $target
     * @param Json $json
     * @param FlashFire $flashFire
     * @param ConsulService $consul
     *
     * @param $em
     */
    public function __construct(
        Target $target,
        Json $json,
        FlashFire $flashFire,
        $em,
        ConsulService $consul
    ) {
        $this->target = $target;
        $this->json = $json;
        $this->flashFire = $flashFire;
        $this->consul = $consul;

        $this->em = $em;
        $this->propRepository = $this->em->getRepository(Property::CLASS);
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
        $this->sendToConsul($configuration, $this->target);

        // 7. Update target to new configuration
        $this->target->withConfiguration($configuration);
        $this->em->persist($this->target);
        $this->em->flush();

        $msg = 'Configuration successfully deployed to ' . $this->target->environment()->name();
        $this->flashFire->fire($msg, 'kraken.status', 'success', ['application' => $this->target->application()->id()]);
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @return Configuration
     */
    private function buildConfiguration(Application $application, Environment $environment)
    {
        $uniq = GUID::create()->asHex();
        $uniq = strtolower($uniq);

        $config = (new Configuration)
            ->withId($uniq)
            ->withApplication($application)
            ->withEnvironment($environment);

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

        $properties = $this->propRepository->findBy([
            'application' => $configuration->application(),
            'environment' => $configuration->environment()
        ]);

        foreach ($properties as $property) {
            $schema = $property->schema();

            $uniq = GUID::create()->asHex();
            $uniq = strtolower($uniq);

            $cf = (new ConfigurationProperty)
                ->withId($uniq)
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
     * @param ConfigurationProperty[] $properties
     *
     * @return string
     */
    private function generateJson(array $properties)
    {
        $jsonable  = [];

        foreach ($properties as $prop) {
            if ($prop->isSecure()) {
                $value = '"**ENCRYPTED**"';
            }

            // db values are json, so must be first decoded
            // This decoded/reencode step allows us to offload some validation to the json process
            $value = $this->json->decode($prop->value());

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
}
