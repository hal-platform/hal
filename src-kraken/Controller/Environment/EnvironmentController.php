<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EnvironmentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;
    private $configurationRepo;

    /**
     * @param TemplateInterface $template
     * @param Environment $environment
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, Environment $environment, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->environment = $environment;
        $this->propertyRepo = $em->getRepository(Property::CLASS);
        $this->configurationRepo = $em->getRepository(Configuration::CLASS);
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $context = [
            'environment' => $this->environment,
            'canRemove' => $this->canRemoveEnvironment()
        ];

        $this->template->render($context);
    }

    /**
     * @return bool
     */
    private function canRemoveEnvironment()
    {
        if ($property = $this->propertyRepo->findOneBy(['environment' => $this->environment])) {
            return false;
        }

        if ($config = $this->configurationRepo->findOneBy(['environment' => $this->environment])) {
            return false;
        }

        return true;
    }
}
