<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\ACL;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Service\Exception\QKSConnectionException;
use QL\Kraken\Service\DeploymentService;
use QL\Kraken\Service\ServiceException;
use QL\Panthor\ControllerInterface;

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
    private $snapshotRepo;

    /**
     * @type ACL
     */
    private $acl;

    /**
     * @param EntityManagerInterface $em
     * @param DeploymentService $deployer
     *
     * @param Configuration $configuration
     * @param User $currentUser
     *
     * @param Flasher $flasher
     * @param callable $random
     * @param ACL $acl
     */
    public function __construct(
        EntityManagerInterface $em,
        DeploymentService $deployer,

        Configuration $configuration,
        User $currentUser,

        Flasher $flasher,
        callable $random,
        ACL $acl
    ) {
        $this->deployer = $deployer;
        $this->flasher = $flasher;

        $this->configuration = $configuration;
        $this->currentUser = $currentUser;

        $this->random = $random;
        $this->acl = $acl;

        $this->em = $em;
        $this->targetRepo = $this->em->getRepository(Target::CLASS);
        $this->snapshotRepo = $this->em->getRepository(Snapshot::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        // 1. Permission check
        $this->acl->requireDeployPermissions($this->configuration->application(), $this->configuration->environment());

        // 2. Find target
        $target = $this->targetRepo->findOneBy([
            'application' => $this->configuration->application(),
            'environment' => $this->configuration->environment()
        ]);

        // 3. Create a configuration for this environment
        $configuration = $this->buildConfiguration($target->application(), $target->environment());

        // 4. Create new properties from old properties
        $properties = $this->buildProperties($this->configuration, $configuration);

        // 5. Deploy
        $status = $this->deploy($target, $configuration, $properties);

        // 6. And finally, go away.
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
     * @return Snapshot[]
     */
    private function buildProperties(Configuration $source, Configuration $new)
    {
        $configuration = [];

        $properties = $this->snapshotRepo->findBy([
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
     * @param Configuration $configuration
     * @param Snapshot[] $properties
     *
     * @return Flasher|bool|null
     */
    private function deploy(Target $target, Configuration $configuration, array $properties)
    {
        try {
            $status = $this->deployer->deploy($target, $configuration, $properties);

        } catch (QKSConnectionException $ex) {
            return $this->flasher->withFlash($ex->getMessage(), 'error');

        } catch (ServiceException $ex) {
            return $this->flasher->withFlash($ex->getMessage(), 'error');
        }

        return $status;
    }

    /**
     * @param Target $target
     * @param Flasher|bool|null $status
     *
     * @return void
     */
    private function redirect(Target $target, $status)
    {
        if ($status instanceof Flasher) {
            return $this->flasher->load('kraken.status', ['application' => $target->application()->id()]);
        }

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
