<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use PDO;

/**
 * @api
 */
class ServerService
{
    use QueryTrait;

    const PRIMARY_KEY = 'ServerId';
    const Q_LIST = 'SELECT srv.ServerId, srv.HostName, env.Name AS Environment FROM Servers AS srv INNER JOIN Environments as env ON (srv.EnvId = env.EnvId)';
    const Q_INSERT = 'INSERT INTO Servers (HostName, EnvId) VALUES (:hostname, :envid)';

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
        return $this->insert($this->db, self::Q_INSERT, [
            [':hostname', $hostname, PDO::PARAM_STR],
            [':envid', $envId, PDO::PARAM_INT],
        ]);
    }
}
