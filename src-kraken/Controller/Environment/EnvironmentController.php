<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var EntityRepository
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
