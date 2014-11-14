<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Doctrine;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\AuditLog;
use QL\Hal\Core\Entity\Session;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\LazyUserHelper;
use ReflectionClass;
use Zend\Ldap\Ldap;

class EntityChangeLogger
{
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LazyUserHelper
     */
    private $userHelper;

    /**
     * @param LazyUserHelper $userHelper
     */
    public function __construct(LazyUserHelper $userHelper)
    {
        $this->userHelper = $userHelper;
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
        $this->em = $event->getEntityManager();
        $uow = $this->em->getUnitOfWork();

        // Entity Insertions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->log($entity, self::ACTION_CREATE, $uow);
        }

        // Entity Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->log($entity, self::ACTION_UPDATE, $uow);
        }

        // Entity Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->log($entity, self::ACTION_DELETE, $uow);
        }
    }

    /**
     * Prepare and persist a log entry
     *
     * @param $entity
     * @param $action
     * @param $uow
     */
    private function log($entity, $action, UnitOfWork $uow)
    {
        // prevent logging loop
        if ($entity instanceof AuditLog || $entity instanceof Session || $entity instanceof User) {
            return;
        }

        $reflect = new ReflectionClass($entity);

        // figure out the entity primary id
        if ($action == self::ACTION_CREATE) {
            $id = '?';
        } else {
            $id = (array)$uow->getEntityIdentifier($entity);
            $id = reset($id);
        }

        // figure out what data to show
        if ($action == self::ACTION_DELETE) {
            // deleted data (show state before delete)
            $data = [];
            foreach ($reflect->getProperties() as $property) {
                $property->setAccessible(true);
                $data[$property->getName()] = $property->getValue($entity);
            }
        } else {
            // updated data (show changes only)
            $data = $uow->getEntityChangeSet($entity);

            // doctrine is weird
            foreach ($data as $k => &$v) {
                if (is_array($v) && count($v) === 2 && $v[0] === null) {
                    $v = array_pop($v);
                }
            }
        }

        $log = new AuditLog;

        $sessionUser = $this->userHelper->getUser();
        $user = $this->em->find('QL\\Hal\\Core\\Entity\\User', $sessionUser->getId());
        $log->setUser($user);

        $log->setRecorded($this->getTimepoint());
        $log->setEntity(sprintf(
            '%s:%s',
            $reflect->getShortName(),
            $id
        ));
        $log->setAction($action);
        $log->setData(json_encode($data));

        $this->em->persist($log);
        $uow->computeChangeSet($this->em->getClassMetadata('QL\\Hal\\Core\\Entity\\AuditLog'), $log);
    }

    /**
     * Get a Timepoint representing the current time
     *
     * @return Timepoint
     */
    private function getTimepoint()
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        return new TimePoint(
            $now->format('Y'),
            $now->format('m'),
            $now->format('d'),
            $now->format('H'),
            $now->format('i'),
            $now->format('s'),
            'UTC'
        );
    }
}