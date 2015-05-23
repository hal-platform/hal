<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Entity\Target;

class ConfigurationDiffService
{
    /**
     * @type EntityRepository
     */
    private $schemaRepo;
    private $propertyRepo;
    private $configurationPropertyRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
        $this->propertyRepo = $em->getRepository(Property::CLASS);
        $this->configurationPropertyRepo = $em->getRepository(ConfigurationProperty::CLASS);
    }

    /**
     * Read property/schema for this application/environment and return a list of Diffs
     *
     * Used to compare a snapshot to the latest configuration.
     *
     * @todo this should be cached heavily
     *
     * @param Application $application
     * @param Environment $environment
     *
     * @return Diff[]
     */
    public function resolveLatestConfiguration(Application $application, Environment $environment)
    {
        $configuration = [];

        $schema = $this->schemaRepo->findBy([
            'application' => $application
        ]);

        $properties = $this->propertyRepo->findBy([
            'application' => $application,
            'environment' => $environment
        ]);

        foreach ($schema as $schema) {
            $diff = $this->diffSchema($configuration, $schema);
            $configuration[$diff->key()] = $diff;
        }

        foreach ($properties as $property) {
            $diff = $this->diffProperty($configuration, $property);
            $configuration[$diff->key()] = $diff;
        }

        uasort($configuration, function($a, $b) {
            return strcasecmp($a->key(), $b->key());
        });

        return $configuration;
    }

    /**
     * Build a list of Diffs from a snapshot.
     *
     * Used to compare two snapshots.
     *
     * @param Configuration $snapshot
     *
     * @return Diff[]
     */
    public function resolveConfiguration(Configuration $snapshot)
    {
        $configuration = [];

        // Add properties from configuration
        $properties = $this->configurationPropertyRepo->findBy(['configuration' => $snapshot]);

        foreach ($properties as $property) {
            if (!$schema = $property->schema()) {
                $schema = (new Schema)
                    ->withKey($property->key())
                    ->withDataType($property->dataType())
                    ->withIsSecure($property->isSecure())
                    ->withApplication($snapshot->application());
            }

            $property = (new Property)
                ->withValue($property->value())
                ->withSchema($schema)
                ->withApplication($snapshot->application())
                ->withEnvironment($snapshot->environment());

            $diff = $this->diffProperty($configuration, $property);

            $configuration[$diff->key()] = $diff;
        }

        uasort($configuration, function($a, $b) {
            return strcasecmp($a->key(), $b->key());
        });

        return $configuration;
    }

    /**
     * Compare a deployed configuration snapshot to a list of Diffs
     *
     * @param Configuration $configuration
     * @param Diff[] $latestConfiguration
     *
     * @return Diff[]
     */
    public function diff(Configuration $configuration, array $latestConfiguration = [])
    {
        $diffed = $latestConfiguration;

        // Add properties from configuration
        $properties = $this->configurationPropertyRepo->findBy(['configuration' => $configuration]);
        foreach ($properties as $property) {
            $diff = $this->diffConfigurationProperty($diffed, $property);
            $diffed[$diff->key()] = $diff;
        }

        foreach ($diffed as $diff) {
            $this->determineChange($diff);
        }

        return $diffed;
    }

    /**
     * @param array $configuration
     * @param Schema $schema
     *
     * @return Diff
     */
    private function diffSchema(array $configuration, Schema $schema)
    {
        $key = $schema->key();
        $diff = $this->getDiff($configuration, $key);

        return $diff->withSchema($schema);
    }

    /**
     * @param array $configuration
     * @param Property $property
     *
     * @return Diff
     */
    private function diffProperty(array $configuration, Property $property)
    {
        $key = $property->schema()->key();
        $diff = $this->getDiff($configuration, $key);

        return $diff
            ->withProperty($property)
            ->withSchema($property->schema());
    }

    /**
     * @param array $configuration
     * @param ConfigurationProperty $property
     *
     * @return Diff
     */
    private function diffConfigurationProperty(array $configuration, ConfigurationProperty $property)
    {
        $key = $property->key();
        $diff = $this->getDiff($configuration, $key);

        return $diff->withConfiguration($property);
    }

    /**
     * @param Diff $diff
     *
     * @return void
     */
    private function determineChange(Diff $diff)
    {
        // deployed and new value are missing: no change
        if (!$diff->configuration() && !$diff->property()) {
            return $diff->withIsChanged(false);

        // one missing: change
        } elseif ($diff->configuration() xor $diff->property()) {
            return $diff->withIsChanged(true);
        }

        // Compare latest property with deployed configuration property
        if ($diff->property()) {
            // @todo make better
            if ($diff->property()->value() === $diff->configuration()->value()) {
                return $diff->withIsChanged(false);
            }
        }

        $diff->withIsChanged(true);
    }

    /**
     * @param array configuration
     * @param string $key
     *
     * @return Diff
     */
    private function getDiff(array $configuration, $key)
    {
        if (isset($configuration[$key])) {
            return $configuration[$key];
        }

        return new Diff($key);
    }
}
