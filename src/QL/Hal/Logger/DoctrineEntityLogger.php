<?php

namespace QL\Hal\Logger;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use MCP\Corp\Account\User as LdapUser;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\Log;
use QL\Hal\Core\Entity\Session;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Helpers\LazyUserHelper;
use Zend\Ldap\Ldap;

/**
 * Doctrine Entity Change Tracking Logger
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class DoctrineEntityLogger
{
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
    public function __construct(
        LazyUserHelper $userHelper
    ) {
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
            $this->log($entity, 'CREATE', $uow);
        }

        // Entity Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->log($entity, 'UPDATE', $uow);
        }

        // Entity Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->log($entity, 'DELETE', $uow);
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
        if ($entity instanceof Log || $entity instanceof Session) {
            return;
        }

        // figure out the entity primary id
        $id = (array)$uow->getEntityIdentifier($entity);
        $id = reset($id);

        $log = new Log();
        $log->setUser($this->getUser($this->userHelper->getUser()));
        $log->setRecorded($this->getTimepoint());
        $log->setEntity(sprintf(
            '%s:%s',
            get_class($entity),
            $id
        ));
        $log->setAction($action);
        $log->setData(json_encode(
            $uow->getEntityChangeSet($entity)
        ));

        $this->em->persist($log);
        $uow->computeChangeSet($this->em->getClassMetadata('QL\\Hal\\Core\\Entity\\Log'), $log);
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

    /**
     * Exchange an LDAP user for a Doctrine user
     *
     * @param LdapUser $user
     * @return User
     */
    private function getUser(LdapUser $user)
    {
        return $this->em->find('QL\\Hal\\Core\\Entity\\User', $user->commonId());
    }
}
