<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use PDO;

/**
 * @internal
 */
trait QueryTrait
{
    /**
     * Lists all records
     *
     * @param PDO $db
     * @param string $query
     * @param string $primaryKey
     * @return array
     */
    private function selectAll(PDO $db, $query, $primaryKey)
    {
        $ret = [];
        $stmt = $db->prepare($query);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ret[$row[$primaryKey]] = $row;
        }
        return $ret;
    }

    /**
     * @param PDO $db
     * @param string $query
     * @param array $data This is an array of arrays that contain the 3 bits of
     *    bind info needed for bindValue calls.
     * @return int
     */
    private function insert(PDO $db, $query, array $data)
    {
        $stmt = $db->prepare($query);
        foreach ($data as $bindInfo) {
            $stmt->bindValue($bindInfo[0], $bindInfo[1], $bindInfo[2]);
        }
        $stmt->execute();
        return $db->lastInsertId();
    }

    /**
     * @param PDO $db
     * @param string $listQuery
     * @param string $primaryKey
     * @param int $id
     * @return null|array
     */
    private function selectOne(PDO $db, $listQuery, $primaryKey, $id)
    {
        $query = $listQuery . ' WHERE ' . $primaryKey . ' = :id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) < 1) {
            return null;
        }
        return $result[0];
    }

    /**
     * @param PDO $db
     * @param string $listQuery
     * @param string $uniqueField
     * @param string $name
     * @return null|array
     */
    private function getByName(PDO $db, $listQuery, $uniqueField, $name)
    {
        $query = $listQuery . ' WHERE ' . $uniqueField . '= :name';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) < 1) {
            return null;
        } else {
            return $result[0];
        }
    }
         
    /**
     * @param PDO $db
     * @param $query
     * @return int
     */
    private function countStar(PDO $db, $query)
    {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_NUM);
        return $result[0][0];
    }
}
