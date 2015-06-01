<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Service\ConsulResponse;
use QL\Kraken\Service\ConsulService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class SnapshotController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Configuration
     */
    private $configuration;

    /**
     * @type ConsulService
     */
    private $consul;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type EntityRepository
     */
    private $targetRepo;
    private $snapshotRepo;

    /**
     * @param TemplateInterface $template
     * @param Configuration $configuration
     * @param ConsulService $consul
     * @param EntityManagerInterface $em
     * @param Json $json
     */
    public function __construct(
        TemplateInterface $template,
        Configuration $configuration,
        ConsulService $consul,
        EntityManagerInterface $em,
        Json $json
    ) {
        $this->template = $template;
        $this->configuration = $configuration;

        $this->consul = $consul;
        $this->json = $json;

        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->snapshotRepo = $em->getRepository(Snapshot::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $target = $this->targetRepo->findOneBy(['configuration' => $this->configuration]);

        $properties = $this->snapshotRepo->findBy([
            'configuration' => $this->configuration
        ], ['key' => 'ASC']);

        $isDeployed = ($target && $target->configuration()->id() === $this->configuration->id());

        $checksums = ($isDeployed) ? $this->consul->getChecksums($target) : [];

        $context = [
            'application' => $this->configuration->application(),
            'configuration' => $this->configuration,

            'properties' => $properties,
            'target' => $target,

            'is_deployed' => $isDeployed,
            'checksums' => $checksums,
            'audits' => $this->formatAuditLogs($this->configuration->audit())
        ];

        $this->template->render($context);
    }

    /**
     * @param string|null $audits
     *
     * @return ConsulResponse[]
     */
    private function formatAuditLogs($audits)
    {
        if (!$audits) {
            return [];
        }

        $decoded = $this->json->decode($audits);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $log) {
            if (!array_key_exists('key', $log)) continue;
            if (!array_key_exists('type', $log)) continue;
            if (!array_key_exists('detail', $log)) continue;

            $normalized[] = (new ConsulResponse($log['key'], $log['type']))
                ->withDetail($log['detail']);
        }

        usort($normalized, function($a, $b) {
            if ($a->type() === $a->type()) {
                // check key name
                return strcasecmp($a->key(), $b->key());
            }

            if ($a->type() === 'update') {
                return 1;
            }

            return -1;
        });

        return $normalized;
    }
}
