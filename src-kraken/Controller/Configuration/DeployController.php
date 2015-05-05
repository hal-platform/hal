<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeployController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type Target
     */
    private $target;

    /**
     * @type EntityRepository
     */
    private $schemaRepository;
    private $propRepository;
    private $configPropRepository;

    /**
     * @param TemplateInterface $template
     * @param Target $target
     *
     * @param $em
     *
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        Target $target,
        $em
    ) {
        $this->template = $template;
        $this->target = $target;

        $this->em = $em;
        $this->schemaRepository = $this->em->getRepository(Schema::CLASS);
        $this->propRepository = $this->em->getRepository(Property::CLASS);
        $this->configPropRepository = $this->em->getRepository(ConfigurationProperty::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $configuration = $this->buildConfiguration($this->target->application(), $this->target->environment());

        // Add "Deployed" configuration
        if ($this->target->configuration()) {
            // @todo verify checksum against consul checksum

            $deployeds = $this->configPropRepository->findBy(['configuration' => $this->target->configuration()]);
            foreach ($deployeds as $deployed) {
                $this->appendDeployedConfiguration($configuration, $deployed);
            }
        }

        foreach ($configuration as &$config) {
            $config['changed'] = $this->determineChange($config);
        }

        $context = [
            'target' => $this->target,
            'application' => $this->target->application(),
            'environment' => $this->target->environment(),
            'configuration' => $configuration
        ];

        $this->template->render($context);
    }

    /**
     * @todo this should be cached heavily
     *
     * @param Application $application
     * @param Environment $environment
     *
     * @return Property|Schema[]
     */
    private function buildConfiguration(Application $application, Environment $environment)
    {
        $configuration = [];

        $schema = $this->schemaRepository->findBy([
            'application' => $application
        ], ['key' => 'ASC']);

        $properties = $this->propRepository->findBy([
            'application' => $application,
            'environment' => $environment
        ]);

        foreach ($schema as $schema) {
            $this->appendSchema($configuration, $schema);
        }

        foreach ($properties as $property) {
            $this->appendProperty($configuration, $property);
        }

        return $configuration;
    }

    /**
     * @param array $configuration
     * @param string $key
     *
     * @return array
     */
    private function preparePartial(array $configuration, $key)
    {
        $partial = [
            'schema' => null,
            'property' => null,
            'deployed' => null,
            'changed' => false
        ];

        if (isset($configuration[$key])) {
            $partial = array_replace($partial, $configuration[$key]);
        }

        return $partial;
    }

    /**
     * @param array $configuration
     * @param Schema $schema
     *
     * @return void
     */
    private function appendSchema(array &$configuration, Schema $schema)
    {
        $key = $schema->key();

        $partial = $this->preparePartial($configuration, $key);
        $partial['schema'] = $schema;

        $configuration[$key] = $partial;
    }

    /**
     * @param array $configuration
     * @param Property $property
     *
     * @return void
     */
    private function appendProperty(array &$configuration, Property $property)
    {
        $key = $property->schema()->key();

        // Ensure Schema is present
        $this->appendSchema($configuration, $property->schema());

        // Add Property
        $partial = $this->preparePartial($configuration, $key);
        $partial['property'] = $property;
        $partial['schema'] = $property->schema();

        $configuration[$key] = $partial;
    }

    /**
     * @param array $configuration
     * @param ConfigurationProperty $property
     *
     * @return void
     */
    private function appendDeployedConfiguration(array &$configuration, ConfigurationProperty $property)
    {
        $key = $property->key();

        // Add Property
        $partial = $this->preparePartial($configuration, $key);
        $partial['deployed'] = $property;

        $configuration[$key] = $partial;
    }

    /**
     * @param array $partial
     *
     * @return bool
     */
    private function determineChange(array $partial)
    {
        // deployed and new value are missing: no change
        if (!$partial['deployed'] && !$partial['property']) {
            return false;

        // one missing: change
        } elseif ($partial['deployed'] xor $partial['property']) {
            return true;
        }

        if ($partial['property']) {
            // @todo make better
            if ($partial['property']->value() === $partial['deployed']->value()) {
                return false;
            }
        }

        return true;
    }
}
