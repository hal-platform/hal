<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\ACL;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RemovePropertyController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Property
     */
    private $property;

    /**
     * @type EntityRepository
     */
    private $snapshotRepo;
    private $targetRepo;

    /**
     * @type ACL
     */
    private $acl;

    /**
     * @param TemplateInterface $template
     * @param Property $property
     * @param EntityManagerInterface $em
     * @param ACL $acl
     */
    public function __construct(
        TemplateInterface $template,
        Property $property,
        EntityManagerInterface $em,
        ACL $acl
    ) {
        $this->template = $template;
        $this->property = $property;

        $this->snapshotRepo = $em->getRepository(Snapshot::CLASS);
        $this->targetRepo = $em->getRepository(Target::CLASS);

        $this->acl = $acl;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $this->acl->requireDeployPermissions($this->property->application(), $this->property->environment());

        $context = [
            'application' => $this->property->application(),
            'environment' => $this->property->environment(),
            'property' => $this->property,
            'deployed' => $this->getDeployedProperty()
        ];

        $this->template->render($context);
    }

    /**
     * Get the current config property if it is actively deployed, so we can display a warning.
     *
     * @return ConfigurationProperty|null
     */
    private function getDeployedProperty()
    {
        $target = $this->targetRepo->findOneBy([
            'application' => $this->property->application(),
            'environment' => $this->property->environment()
        ]);

        // No target or configuration was never deployed
        if (!$target || !$target->configuration()) {
            return null;
        }

        $currentProperty = $this->snapshotRepo->findBy([
            'configuration' => $target->configuration(),
            'property' => $this->property
        ]);

        return $currentProperty;
    }
}
