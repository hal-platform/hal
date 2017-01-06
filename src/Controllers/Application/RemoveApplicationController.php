<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class RemoveApplicationController implements ControllerInterface
{
    const SUCCESS = 'Application "%s" removed.';
    const ERR_HAS_DEPLOYMENTS = 'Cannot remove application. All server deployments must first be removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Application $application
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        Application $application
    ) {
        $this->em = $em;

        $this->flasher = $flasher;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if ($this->doesApplicationHaveChildren()) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_DEPLOYMENTS, 'error')
                ->load('application', ['application' => $this->application->id()]);
        }

        $this->em->remove($this->application);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $this->application->key());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('applications');
    }

    /**
     * @return bool
     */
    private function doesApplicationHaveChildren()
    {
        $targets = $this->em
            ->getRepository(Deployment::class)
            ->findOneBy(['application' => $this->application]);

        if (count($targets) > 0) return true;

        $builds = $this->em
            ->getRepository(Build::class)
            ->findOneBy(['application' => $this->application]);

        if (count($builds) > 0) return true;

        $deployments = $this->em
            ->getRepository(Push::class)
            ->findOneBy(['application' => $this->application]);

        if (count($deployments) > 0) return true;

        return false;
    }
}
