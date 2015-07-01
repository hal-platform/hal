<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;

class RemoveEnvironmentHandler implements ControllerInterface
{
    const SUCCESS = 'Environment "%s" has been removed.';

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param Environment $environment
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     */
    public function __construct(
        Environment $environment,
        EntityManagerInterface $em,
        Flasher $flasher
    ) {
        $this->environment = $environment;
        $this->flasher = $flasher;
        $this->em = $em;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $name = $this->environment->name();

        $this->em->remove($this->environment);
        $this->em->flush();

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $name), 'success')
            ->load('kraken.environments');
    }
}
