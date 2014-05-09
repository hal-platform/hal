<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use PDO;
use PDOException;

/**
 * @api
 */
class ServerService
{
    use QueryTrait;

    const PRIMARY_KEY = 'ServerId';
    const Q_LIST = 'SELECT srv.ServerId, srv.HostName, env.ShortName AS Environment FROM Servers AS srv INNER JOIN Environments as env ON (srv.EnvironmentId = env.EnvironmentId) ORDER BY env.DispOrder ASC, srv.HostName';
    const Q_SELECT_ONE = 'SELECT srv.ServerId, srv.HostName, env.ShortName AS Environment FROM Servers AS srv INNER JOIN Environments as env ON (srv.EnvironmentId = env.EnvironmentId) WHERE ServerId = :id';
    const Q_INSERT = 'INSERT INTO Servers (HostName, EnvironmentId) VALUES (:hostname, :envid)';
    const Q_DELETE = 'DELETE FROM Servers WHERE ServerId = :id';

    /**
     * @var PDO
     */
    private $db;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @return array
     */
    public function listAll()
    {
        return $this->selectAll($this->db, self::Q_LIST, self::PRIMARY_KEY);
    }

    /**
     * @param string $hostname
     * @param int $envId
     * @return int
     */
    public function create($hostname, $envId)
    {
        try {
            return $this->insert($this->db, self::Q_INSERT, [
                    [':hostname', $hostname, PDO::PARAM_STR],
                    [':envid', $envId, PDO::PARAM_INT],
                ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare(self::Q_SELECT_ONE);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            return null;
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    /**
     *
     *  @param $id
     */
    public function remove($id)
    {
        $stmt = $this->db->prepare(self::Q_DELETE);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

}