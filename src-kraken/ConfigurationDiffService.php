<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Core\Entity\Target;

class ConfigurationDiffService
{
    /**
     * @type EntityRepository
     */
    private $schemaRepo;
    private $propertyRepo;
    private $snapshotRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
        $this->propertyRepo = $em->getRepository(Property::CLASS);
        $this->snapshotRepo = $em->getRepository(Snapshot::CLASS);
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
     * Build a list of Diffs from a configuration.
     *
     * Used to compare two snapshots.
     *
     * @param Configuration $source
     *
     * @return Diff[]
     */
    public function resolveConfiguration(Configuration $source)
    {
        $configuration = [];

        // Add properties from configuration
        $snapshots = $this->snapshotRepo->findBy(['configuration' => $source]);

        foreach ($snapshots as $snapshot) {
            if (!$schema = $snapshot->schema()) {
                $schema = (new Schema)
                    ->withKey($snapshot->key())
                    ->withDataType($snapshot->dataType())
                    ->withIsSecure($snapshot->isSecure())
                    ->withApplication($source->application());
            }

            $property = (new Property)
                ->withValue($snapshot->value())
                ->withSchema($schema)
                ->withApplication($source->application())
                ->withEnvironment($source->environment());

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
     * @param Configuration|null $configuration
     * @param Diff[] $latestConfiguration
     *
     * @return Diff[]
     */
    public function diff(Configuration $configuration = null, array $latestConfiguration = [])
    {
        $diffed = $latestConfiguration;

        if ($configuration) {
            // Add properties from configuration
            $snapshots = $this->snapshotRepo->findBy(['configuration' => $configuration]);
            foreach ($snapshots as $property) {
                $diff = $this->diffSnapshot($diffed, $property);
                $diffed[$diff->key()] = $diff;
            }
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
     * @param Snapshot $snapshot
     *
     * @return Diff
     */
    private function diffSnapshot(array $configuration, Snapshot $snapshot)
    {
        $key = $snapshot->key();
        $diff = $this->getDiff($configuration, $key);

        return $diff->withSnapshot($snapshot);
    }

    /**
     * @param Diff $diff
     *
     * @return void
     */
    private function determineChange(Diff $diff)
    {
        // deployed and new value are missing: no change
        if (!$diff->snapshot() && !$diff->property()) {
            return $diff->withIsChanged(false);

        // one missing: change
        } elseif ($diff->snapshot() xor $diff->property()) {
            return $diff->withIsChanged(true);
        }

        // Compare latest property with deployed configuration property
        if ($diff->property()) {
            // @todo make better
            if ($diff->property()->value() === $diff->snapshot()->value()) {
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
