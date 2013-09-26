<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use PDO;

class ArrangementService
{
    use QueryTrait;

    const PRIMARY_KEY = 'ArrangementId';
    const UNIQUE_FIELD = 'ShortName';
    const Q_LIST = 'SELECT ArrangementId, ShortName, Name FROM Arrangements';
    const Q_INSERT = 'INSERT INTO Arrangements (ShortName, Name) VALUES (:shortname, :name)';
    const Q_GET_ID = 'SELECT ArrangementId from Arrangements';

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
     * @param string $shortName
     * @param string $fullName
     * @return int
     */
    public function create($shortName, $fullName)
    {
        return $this->insert($this->db, self::Q_INSERT, [
            [':shortname', $shortName, PDO::PARAM_STR],
            [':name', $fullName, PDO::PARAM_STR],
        ]);
    }

    /**
     * @param string $shortName
     * @return int
     */
    public function getIdByShortName($shortName)
    {
        return $this->selectOne($this->db, self::Q_GET_ID, self::UNIQUE_FIELD, $shortName);
    }
}
