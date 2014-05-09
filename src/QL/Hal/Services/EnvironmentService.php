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
class EnvironmentService
{
    use QueryTrait;

    const PRIMARY_KEY = 'EnvironmentId';
    const Q_LIST = 'SELECT EnvironmentId, ShortName, DispOrder FROM Environments ORDER BY DispOrder ASC';
    const Q_SELECT_ONE = 'SELECT EnvironmentId, ShortName, DispOrder FROM Environments WHERE EnvironmentId = :id';
    const Q_UPDATE_ORDER = 'UPDATE Environments SET DispOrder = :disp WHERE EnvironmentId = :envid';
    const Q_INSERT = 'INSERT INTO Environments (ShortName, DispOrder) SELECT :name, 1 + COALESCE((SELECT IFNULL(MAX(DispOrder),0) FROM Environments))';
    const Q_DELETE = 'DELETE FROM Environments WHERE EnvironmentId = :id';

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
     * @param string $name
     * @return int
     */
    public function create($name)
    {
        return $this->insert($this->db, self::Q_INSERT, [
            [':name', $name, PDO::PARAM_STR]
        ]);
    }

    /**
     * @param array $orderMapping Array with keys as EnvId and Values as DispOrder
     * @return null
     */
    public function updateOrder(array $orderMapping)
    {
        $stmt = $this->db->prepare(self::Q_UPDATE_ORDER);
        foreach ($orderMapping as $envId => $dispOrder) {
            $stmt->bindValue(':envid', $envId, PDO::PARAM_INT);
            $stmt->bindValue(':disp', $dispOrder, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * @param int $envId
     * @return array|null
     */
    public function getById($envId)
    {
        $stmt = $this->db->prepare(self::Q_SELECT_ONE);
        $stmt->bindValue(':id', $envId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) < 1) {
            return null;
        }
        return $result[0];
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