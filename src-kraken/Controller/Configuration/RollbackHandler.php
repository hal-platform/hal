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
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;
use QL\Kraken\Service\ConsulConnectionException;
use QL\Kraken\Service\DeploymentService;
use QL\Panthor\ControllerInterface;
use Slim\Exception\Stop as StopException;

class RollbackHandler implements ControllerInterface
{
    const SUCCESS = 'Configuration successfully deployed to %s';

    const ERR_THIS_IS_SUPER_BAD = 'A serious error has occured. Consul was partially updated.';
    const ERR_CONSUL_FAILURE = 'Errors occured while updating Consul. No updates were made.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type DeploymentService
     */
    private $deployer;

    /**
     * @type Configuration
     */
    private $configuration;

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
    private $targetRepo;
    private $configurationPropertyRepo;

    /**
     * @param EntityManagerInterface $em
     * @param DeploymentService $deployer
     *
     * @param Configuration $configuration
     * @param User $currentUser
     *
     * @param Flasher $flasher
     * @param callable $random
     */
    public function __construct(
        EntityManagerInterface $em,
        DeploymentService $deployer,

        Configuration $configuration,
        User $currentUser,

        Flasher $flasher,
        callable $random
    ) {
        $this->deployer = $deployer;
        $this->flasher = $flasher;

        $this->configuration = $configuration;
        $this->currentUser = $currentUser;

        $this->random = $random;

        $this->em = $em;
        $this->targetRepo = $this->em->getRepository(Target::CLASS);
        $this->configurationPropertyRepo = $this->em->getRepository(ConfigurationProperty::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        // 1. Find target
        $target = $this->targetRepo->findOneBy([
            'application' => $this->configuration->application(),
            'environment' => $this->configuration->environment()
        ]);

        // 1. Create a configuration for this environment
        $configuration = $this->buildConfiguration($target->application(), $target->environment());

        // 2. Create new properties from old properties
        $properties = $this->buildProperties($this->configuration, $configuration);

        // 3. Deploy
        try {
            $status = $this->deployer->deploy($target, $configuration, $properties);

        } catch (ConsulConnectionException $ex) {
            return $this->flasher
                ->withFlash($ex->getMessage(), 'error')
                ->load('kraken.rollback', ['configuration' => $this->configuration->id()]);
        }

        // 4. And finally, go away.
        $this->redirect($target, $status);
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
     * @param Configuration $source
     * @param Configuration $new
     *
     * @return ConfigurationProperty[]
     */
    private function buildProperties(Configuration $source, Configuration $new)
    {
        $configuration = [];

        $properties = $this->configurationPropertyRepo->findBy([
            'configuration' => $source,
        ]);

        foreach ($properties as $oldProperty) {
            $property = clone $oldProperty;

            $id = call_user_func($this->random);

            $property
                ->withId($id)
                ->withChecksum('')
                ->withConfiguration($new);

            $configuration[$property->key()] = $property;
        }

        return $configuration;
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
