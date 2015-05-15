<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Entity\Target;
use QL\Kraken\Utility\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ManageApplicationController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type EntityRepository
     */
    private $environmentRepo;
    private $targetRepo;
    private $schemaRepo;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param EntityManager $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        EntityManager $em
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->environmentRepo = $em->getRepository(Environment::CLASS);
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $schema = $this->schemaRepo->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        $context = [
            'application' => $this->application,
            'targets' => $this->getEnvironmentsAndTargets(),
            'schema' => $schema
        ];

        $this->template->render($context);
    }

    /**
     * @return Environment|Target[]
     */
    private function getEnvironmentsAndTargets()
    {
        $environments = $this->environmentRepo->findBy([], ['name' => 'ASC']);
        if (!$environments) {
            return [];
        }

        usort($environments, $this->environmentSorter());

        $targets = $this->targetRepo->findBy(['application' => $this->application]);
        if (!$targets) {
            return $environments;
        }

        $combined = [];
        foreach ($environments as $env) {
            $combined[$env->id()] = $env;
        }

        foreach ($targets as $target) {
            $linkedId = $target->environment()->id();
            if (isset($combined[$linkedId]) && $combined[$linkedId] === $target->environment()) {
                $combined[$linkedId] = $target;
            }
        }

        return $combined;
    }
}
