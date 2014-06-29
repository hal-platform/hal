<?php
# lib/QL/Hal/Services/Session/Handler/MySQLHandler.php

namespace QL\Hal\Services\Session\Handler;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\Repository\SessionRepository;
use QL\Hal\Core\Entity\Session;
use QL\Hal\Services\Session\Handler;

/**
 *  Doctrine Session Handler
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class DoctrineHandler implements Handler
{
    /**
     *  @var SessionRepository
     */
    private $sessionRepo;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     *  @param SessionRepository $sessionRepo
     *  @param EntityManager $em
     */
    public function __construct(
        SessionRepository $sessionRepo,
        EntityManager $em
    ) {
        $this->sessionRepo = $sessionRepo;
        $this->em = $em;
    }

    /**
     *  Close the current session
     *
     *  @return bool|void
     */
    public function close()
    {
        return true;
    }

    /**
     *  Destroy a session
     *
     *  @param string $session_id
     *  @return bool
     */
    public function destroy($session_id)
    {
        $session = $this->sessionRepo->findOneBy(['id' => $session_id]);

        if (!$session) {
            return false;
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    /**
     *  Cleanup expired sessions
     *
     *  @param int $maxlifetime
     *  @return bool
     */
    public function gc($maxlifetime)
    {
        $limit = DateTime::createFromFormat(
            'U',
            time()-(int)$maxlifetime,
            new DateTimeZone('UTC')
        );

        $limit = new TimePoint(
            $limit->format('Y'),
            $limit->format('n'),
            $limit->format('j'),
            $limit->format('G'),
            $limit->format('i'),
            $limit->format('s'),
            'UTC'
        );

        // weird, this shouldn't be necessary - it should be handled by the hal9000-core timepoint type
        $formatted = $limit->format('Y-m-d H:i:s', 'UTC');

        $dql = 'DELETE QL\Hal\Core\Entity\Session s WHERE s.lastAccess < :limit';
        $this->em->createQuery($dql)
            ->setParameter('limit', $formatted)
            ->execute();
    }

    /**
     *  Initialize session
     *
     *  @param string $save_path
     *  @param string $session_id
     *  @return bool
     */
    public function open($save_path, $session_id)
    {
        return $this->write($session_id, '');
    }

    /**
     *  Retrieve data from session
     *
     *  @param string $session_id
     *  @return string
     */
    public function read($session_id)
    {
        $this->ping($session_id);

        $session = $this->sessionRepo->findOneBy(['id' => $session_id]);

        if (!$session) {
            return '';
        } else {
            return $session->getData();
        }
    }

    /**
     *  Write session data
     *
     *  @param string $session_id
     *  @param string $session_data
     *  @return bool
     */
    public function write($session_id, $session_data)
    {
        $session = $this->sessionRepo->findOneBy(['id' => $session_id]);

        if (!$session) {
            $session = new Session();
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $now = new TimePoint(
            $now->format('Y'),
            $now->format('n'),
            $now->format('j'),
            $now->format('G'),
            $now->format('i'),
            $now->format('s'),
            'UTC'
        );

        $session->setId($session_id);
        $session->setData($session_data);
        $session->setLastAccess($now);

        $this->em->persist($session);
        $this->em->flush();
    }

    /**
     *  Blindly update the last access time for a session
     *
     *  @param $session_id
     */
    private function ping($session_id)
    {
        $session = $this->sessionRepo->findOneBy(['id' => $session_id]);

        if (!$session) {
            $session = new Session();
            $session->setData('');
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $now = new TimePoint(
            $now->format('Y'),
            $now->format('n'),
            $now->format('j'),
            $now->format('G'),
            $now->format('i'),
            $now->format('s'),
            'UTC'
        );

        $session->setId($session_id);
        $session->setLastAccess($now);

        $this->em->persist($session);
        $this->em->flush();
    }
}
