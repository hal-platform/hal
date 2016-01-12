<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\AuditLog;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class AuditLogController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type AuditLog
     */
    private $audit;

    /**
     * @type EntityRepository
     */
    private $environmentRepo;
    private $schemaRepo;
    private $propertyRepo;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param TemplateInterface $template
     * @param AuditLog $audit
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        AuditLog $audit,
        EntityManagerInterface $em,
        Json $json
    ) {
        $this->template = $template;
        $this->audit = $audit;
        $this->json = $json;

        $this->environmentRepo = $em->getRepository(Environment::CLASS);
        $this->schemaRepo = $em->getRepository(Property::CLASS);
        $this->propertyRepo = $em->getRepository(Schema::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $entity = $this->audit->entity();
        list($type, $id) = explode(':', $entity);

        $resource = null;
        if ($type == 'Property') {
            $resource = $this->propertyRepo->find($id);

        } elseif ($type == 'Schema') {
            $resource = $this->schemaRepo->find($id);
        }

        $environment = $changed = null;

        if ($type === 'Property') {
            $decoded = $this->json->decode($this->audit->data());
            $changed = $decoded['value'];

            if (isset($decoded['environment'])) {
                $environment = $this->environmentRepo->find($decoded['environment']);
            }
        }

        $this->template->render([
            'log' => $this->audit,
            'resource' => $resource,
            'resource_type' => $type,
            'resource_id' => $id,

            // Property only
            'environment' => $environment,
            'changed' => $changed
        ]);
    }
}
