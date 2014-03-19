<?php
# lib/QL/Hal/Services/Session/Handler/MySQLHandler.php

namespace QL\Hal\Services\Session\Handler;

use PDO;
use DateTime;
use DateTimeZone;
use QL\Hal\Services\QueryTrait;
use QL\Hal\Services\Session\Handler;

/**
 *  MySQL Session Handler
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class MySQLHandler implements Handler
{
    const TIMEZONE      = 'UTC';
    const DATE_FORMAT   = 'Y-m-d H:i:s';
    const Q_DESTROY     = 'DELETE FROM Sessions WHERE SessionId = :id';
    const Q_PING        = 'UPDATE IGNORE Sessions SET LastAccess = :now WHERE SessionId = :id';
    const Q_READ        = 'SELECT SessionData FROM Sessions WHERE SessionId = :id';
    const Q_WRITE       = 'REPLACE INTO Sessions (SessionId, SessionData, LastAccess) VALUES (:id, :data, :now)';
    const Q_GC          = 'DELETE FROM Sessions WHERE LastAccess < :limit';

    private $db;

    /**
     *  Construct
     *
     *  @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
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
        $statement = $this->db->prepare(self::Q_DESTROY);
        $statement->bindValue('id', $session_id, PDO::PARAM_STR);
        return $statement->execute();
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
            new DateTimeZone(self::TIMEZONE)
        );

        $statement = $this->db->prepare(self::Q_GC);
        $statement->bindValue('limit', $limit->format(self::DATE_FORMAT), PDO::PARAM_STR);
        return $statement->execute();
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

        $statement = $this->db->prepare(self::Q_READ);
        $statement->bindValue('id', $session_id, PDO::PARAM_STR);

        if ($statement->execute()) {
            $data = $statement->fetch(PDO::FETCH_ASSOC);
            return $data['SessionData'];
        } else {
            return '';
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
        $now = new DateTime(
            'now',
            new DateTimeZone(self::TIMEZONE)
        );

        $statement = $this->db->prepare(self::Q_WRITE);
        $statement->bindValue('id', $session_id, PDO::PARAM_STR);
        $statement->bindValue('data', $session_data, PDO::PARAM_STR);
        $statement->bindValue('now', $now->format(self::DATE_FORMAT), PDO::PARAM_STR);
        return $statement->execute();
    }

    /**
     *  Blindly update the last access time for a session
     *
     *  @param $session_id
     */
    private function ping($session_id)
    {
        $now = new DateTime(
            'now',
            new DateTimeZone(self::TIMEZONE)
        );

        $statement = $this->db->prepare(self::Q_PING);
        $statement->bindValue('now', $now->format(self::DATE_FORMAT), PDO::PARAM_STR);
        $statement->bindValue('id', $session_id, PDO::PARAM_STR);
        $statement->execute();
    }
}
