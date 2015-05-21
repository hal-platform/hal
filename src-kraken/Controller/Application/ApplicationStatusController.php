<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Service\ConsulService;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Entity\Target;
use QL\Kraken\Utility\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationStatusController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type ConsulService
     */
    private $consul;

    /**
     * @type EntityRepository
     */
    private $targetRepo;
    private $schemaRepo;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param ConsulService $consul
     * @param EntityManager $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        ConsulService $consul,
        EntityManager $em
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->consul = $consul;

        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $targets = $this->targetRepo->findBy(['application' => $this->application]);

        usort($targets, $this->targetSorter());

        $schema = $this->schemaRepo->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        $context = [
            'application' => $this->application,
            'targets' => $targets,
            'schema' => $schema,
            'checksum_status' => $this->getChecksumStatus($targets)
        ];

        $this->template->render($context);
    }

    /**
     * @param Target[] $targets
     *
     * @return array
     */
    private function getChecksumStatus(array $targets)
    {
        $checksums = [];

        foreach ($targets as $target) {
            if (!$target->configuration()) {
                continue;
            }

            $id = $target->configuration()->id();
            // $knownChecksum = $target->configuration()->checksum();

            // $actualChecksum = $this->consul->getChecksum($target->configuration(), $target);

            // $checksums[$id] = ($actualChecksum === $knownChecksum);
            $checksums[$id] = false;
        }

        return $checksums;
    }
}
