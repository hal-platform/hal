<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Service\Exception\QKSConnectionException;
use QL\Kraken\Service\DeploymentService;
use QL\Kraken\Service\ServiceException;
use QL\Panthor\ControllerInterface;

class DeployHandler implements ControllerInterface
{
    const SUCCESS = 'Configuration successfully deployed to %s';

    const ERR_THIS_IS_SUPER_BAD = 'A serious error has occured. Consul was partially updated.';
    const ERR_CONSUL_FAILURE = 'Errors occured while updating Consul. No updates were made.';

    /**
     * @type DeploymentService
     */
    private $deployer;

    /**
     * @type Target
     */
    private $target;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;

    /**
     * @param EntityManagerInterface $em
     * @param DeploymentService $deployer
     *
     * @param Target $target
     * @param User $currentUser
     *
     * @param Flasher $flasher
     * @param callable $random
     */
    public function __construct(
        EntityManagerInterface $em,
        DeploymentService $deployer,

        Target $target,
        User $currentUser,

        Flasher $flasher,
        callable $random
    ) {
        $this->deployer = $deployer;

        $this->target = $target;
        $this->currentUser = $currentUser;

        $this->flasher = $flasher;
        $this->random = $random;

        $this->propertyRepo = $em->getRepository(Property::CLASS);
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

        // 3. Deploy
        $status = $this->deploy($configuration, $properties);

        if ($status instanceof Flasher) {
            $status->load('kraken.deploy', ['target' => $this->target->id()]);
        }

        // And finally, go away.
        $this->redirect($this->target, $status);
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
     * @return Snapshot[]
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

            $snapshot = (new Snapshot)
                ->withId($id)
                ->withKey($schema->key())
                ->withDataType($schema->dataType())
                ->withIsSecure($schema->isSecure())
                ->withValue($property->value())

                ->withConfiguration($configuration)
                ->withProperty($property)
                ->withSchema($schema);

            $newconfig[$snapshot->key()] = $snapshot;
        }

        return $newconfig;
    }

    /**
     * @param Configuration $configuration
     * @param Snapshot[] $properties
     *
     * @return bool|null
     */
    private function deploy(Configuration $configuration, array $properties)
    {
        try {
            $status = $this->deployer->deploy($this->target, $configuration, $properties);

        } catch (QKSConnectionException $ex) {
            return $this->flasher->withFlash($ex->getMessage(), 'error');

        } catch (ServiceException $ex) {
            return $this->flasher->withFlash($ex->getMessage(), 'error');
        }

        return $status;
    }

    /**
     * @param Target $target
     * @param bool|null $status
     *
     * @throws StopException
     *
     * @return void
     */
    private function redirect(Target $target, $status)
    {
        if ($status === null) {
            // Mixed update. BAD!
            $this->flasher->withFlash(self::ERR_THIS_IS_SUPER_BAD, 'error');

        } elseif (!$status) {
            // True failure.
            $this->flasher->withFlash(self::ERR_CONSUL_FAILURE, 'error');

        } else {
            // Success
            $this->flasher->withFlash(sprintf(self::SUCCESS, $target->environment()->name()), 'success');
        }

        // byebye
        $this->flasher->load('kraken.status', ['application' => $target->application()->id()]);
    }
}
