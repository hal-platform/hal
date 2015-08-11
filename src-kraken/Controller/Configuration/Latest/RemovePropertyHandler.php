<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\ACL;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Property;
use QL\Panthor\ControllerInterface;

class RemovePropertyHandler implements ControllerInterface
{
    const SUCCESS = 'Property "%s" has been removed from configuration.';

    /**
     * @type Property
     */
    private $property;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type ACL
     */
    private $acl;

    /**
     * @param Property $property
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param ACL $acl
     */
    public function __construct(
        Property $property,
        EntityManagerInterface $em,
        Flasher $flasher,
        ACL $acl
    ) {
        $this->property = $property;
        $this->flasher = $flasher;
        $this->em = $em;
        $this->acl = $acl;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $application = $this->property->application();
        $environment = $this->property->environment();

        $this->acl->requireKrakenDeployPermissions($application, $environment);

        $key = $this->property->schema()->key();

        $this->em->remove($this->property);
        $this->em->flush();

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $key), 'success')
            ->load('kraken.configuration.latest', [
                'application' => $application->id(),
                'environment' => $environment->id()
            ]);
    }
}
