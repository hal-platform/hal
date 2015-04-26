<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Encryption;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\PropertySchema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $envRepository;
    private $encRepository;
    private $schemaRepository;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     *
     * @param $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        $em
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->encRepository = $this->em->getRepository(Encryption::CLASS);
        $this->envRepository = $this->em->getRepository(Environment::CLASS);
        $this->schemaRepository = $this->em->getRepository(PropertySchema::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $assigned = $this->encRepository->findBy(['application' => $this->application]);

        $schema = $this->schemaRepository->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        $context = [
            'application' => $this->application,
            'assigned_environment' => $assigned,
            'schema' => $schema
        ];

        $context['environments'] = $this->filterLinkedEnvironments(
            $assigned,
            $this->envRepository->findBy([], ['name' => 'ASC'])
        );

        $this->template->render($context);
    }

    /**
     * @param Encryption[] $assigned
     * @param Environment[] $environments
     *
     * @return Environment[]
     */
    private function filterLinkedEnvironments($assigned, $environments)
    {
        $linked = [];
        foreach ($assigned as $link) {
            $linked[$link->environment()->id()] = true;
        }

        return array_filter($environments, function($env) use ($linked) {
            return !isset($linked[$env->id()]);
        });
    }
}
