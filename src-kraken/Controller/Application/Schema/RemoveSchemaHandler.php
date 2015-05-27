<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Utility\SortingHelperTrait;
use QL\Panthor\ControllerInterface;

class RemoveSchemaHandler implements ControllerInterface
{
    use SortingHelperTrait;

    const SUCCESS = 'Property Schema "%s" has been removed from configuration.';
    const REMOVED_ENV = 'The property has also been removed from the following environment: <b>%s</b>';
    const REMOVED_ENVS = 'The property has also been removed from the following environments: <b>%s</b>';

    /**
     * @type Schema
     */
    private $schema;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;

    /**
     * @param Schema $schema
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     */
    public function __construct(
        Schema $schema,
        EntityManagerInterface $em,
        Flasher $flasher
    ) {
        $this->schema = $schema;
        $this->flasher = $flasher;

        $this->em = $em;
        $this->propertyRepo = $em->getRepository(Property::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $application = $this->schema->application();
        $key = $this->schema->key();

        $removedPropertiesMessage = $this->removePropertiesInAllEnv();

        $this->em->remove($this->schema);
        $this->em->flush();

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $key), 'success', $removedPropertiesMessage)
            ->load('kraken.schema', ['application' => $application->id()]);
    }

    /**
     * @return string|null
     */
    private function removePropertiesInAllEnv()
    {
        $properties = $this->propertyRepo->findBy(['schema' => $this->schema]);
        usort($properties, $this->sorterPropertyByEnvironment());

        if ($properties) {
            $envs = [];
            foreach ($properties as $property) {
                $envs[] = $property->environment()->name();
                $this->em->remove($property);
            }

            $template = (count($envs) === 1) ? self::REMOVED_ENV : self::REMOVED_ENVS;
            return sprintf($template, implode(', ', $envs));
        }

        return null;
    }
}
