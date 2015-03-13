<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use MCP\DataType\Time\Clock;
use QL\Hal\Core\Entity\AuditLog;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\LazyUserHelper;

class EntityChangeLogger
{
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';

    /**
     * @type LazyUserHelper
     */
    private $userHelper;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @param LazyUserHelper $userHelper
     * @param Clock $clock
     */
    public function __construct(LazyUserHelper $userHelper, Clock $clock)
    {
        $this->userHelper = $userHelper;
        $this->clock = $clock;
    }

    /**
     * Listen for Doctrine flush events.
     *
     * This listener will catch any entities or collections scheduled for insert, update, or removal.
     *
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        if (!$user = $this->userHelper->getUser()) {
            return;
        }

        if (!$user = $em->find('QL\Hal\Core\Entity\User', $user->getId())) {
            return;
        }

        // Entity Insertions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($log = $this->log($user, $entity, self::ACTION_CREATE)) {
                $this->persist($em, $uow, $log);
            }
        }

        // Entity Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($log = $this->log($user, $entity, self::ACTION_UPDATE)) {
                $this->addChangeset($log, $uow->getEntityChangeSet($entity));
                $this->persist($em, $uow, $log);
            }
        }

        // Entity Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($log = $this->log($user, $entity, self::ACTION_DELETE)) {
                $this->persist($em, $uow, $log);
            }
        }
    }

    /**
     * Prepare an audit log from a changed entity.
     *
     * @param User $user
     * @param mixed $entity
     * @param string $action
     *
     * @return AuditLog|null
     */
    private function log(User $user, $entity, $action)
    {
        // prevent logging loop
        if ($entity instanceof AuditLog || $entity instanceof User) {
            return;
        }

        // figure out the entity primary id
        $id = '?';
        if (is_callable([$entity, 'getId']) && $entity->getId()) {
            $id = $entity->getId();
        }

        $fqcn = get_class($entity);
        $classname = explode('\\', $fqcn);
        $object = sprintf('%s:%s', array_pop($classname), $id);

        $log = new AuditLog;
        $log->setUser($user);
        $log->setRecorded($this->clock->read());
        $log->setEntity($object);
        $log->setAction($action);
        $log->setData(json_encode($entity));

        return $log;
    }

    /**
     * @param AuditLog $log
     * @param array $changeset
     *
     * @return null
     */
    private function addChangeset(AuditLog $log, array $changeset)
    {
        $data = json_decode($log->getData(), true);

        foreach ($changeset as $field => $properties) {
            if (isset($data[$field])) {
                $data[$field] = [
                    'current' => $properties[0],
                    'new' => $properties[1]
                ];
            }
        }

        $log->setData(json_encode($data));
    }

    /**
     * Persist the audit log.
     *
     * @param EntityManager $em
     * @param UnitOfWork $unit
     * @param AuditLog $log
     *
     * @return null
     */
    private function persist(EntityManager $em, UnitOfWork $unit, AuditLog $log)
    {
        $em->persist($log);

        $meta = $em->getClassMetadata('QL\Hal\Core\Entity\AuditLog');
        $unit->computeChangeSet($meta, $log);
    }
}
