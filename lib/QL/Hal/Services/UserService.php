<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use MCP\DataType\HttpUrl;
use PDO;

/**
 * @api
 */
class UserService
{
    use QueryTrait;

    const PRIMARY_KEY = 'CommonId';
    const Q_LIST = 'SELECT CommonId, UserName, Email, DisplayName, PictureUrl FROM Users ORDER BY DisplayName ASC';
    const Q_ONE = 'SELECT CommonId, UserName, Email, DisplayName, PictureUrl FROM Users';
    const Q_INSERT = 'INSERT INTO Users (CommonId, UserName, Email, DisplayName, PictureUrl) VALUES (:commonId, :userName, :email, :displayName, :pictureUrl)';
    const Q_UPDATE = 'UPDATE Users SET UserName = :userName, Email = :email, DisplayName = :displayName, PictureUrl = :pictureUrl WHERE CommonId = :commonId';
    const Q_COUNT = 'SELECT COUNT(*) FROM Users';
    const Q_PUSHES = 'SELECT COUNT(*) FROM PushLogs WHERE PushCommonId = :commonId';

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

    public function getTotalPushesByCommonId($commonId)
    {
        $stmt = $this->db->prepare(self::Q_PUSHES);
        $stmt->bindValue(':commonId', $commonId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt = $stmt->fetchAll(PDO::FETCH_NUM);
        return $stmt[0][0];
    }

    /**
     * @return array
     */
    public function listAll()
    {
        return $this->selectAll($this->db, self::Q_LIST, self::PRIMARY_KEY);
    }

    /**
     * Creates a new User record
     *
     * Note: does not use QueryTrait->insert() because there is no
     * auto-incrementing primary key for this particular table.
     *
     * @param int $commonId
     * @param string $userName
     * @param string $email
     * @param string $displayName
     * @param HttpUrl $pictureUrl
     * @return null
     */
    public function create($commonId, $userName, $email, $displayName, HttpUrl $pictureUrl)
    {
        $stmt = $this->db->prepare(self::Q_INSERT);
        $stmt->bindValue(':commonId', $commonId, PDO::PARAM_INT);
        $stmt->bindValue(':userName', $userName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':displayName', $displayName, PDO::PARAM_STR);
        $stmt->bindValue(':pictureUrl', $pictureUrl->asString(), PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @param int $commonId
     * @param string $userName
     * @param string $email
     * @param string $displayName
     * @param HttpUrl $pictureUrl
     * @return null
     */
    public function update($commonId, $userName, $email, $displayName, HttpUrl $pictureUrl)
    {
        $stmt = $this->db->prepare(self::Q_UPDATE);
        $stmt->bindValue(':commonId', $commonId, PDO::PARAM_INT);
        $stmt->bindValue(':userName', $userName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':displayName', $displayName, PDO::PARAM_STR);
        $stmt->bindValue(':pictureUrl', $pictureUrl->asString(), PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @param int $commonId
     * @return array|null
     */
    public function getById($commonId)
    {
        return $this->selectOne($this->db, self::Q_ONE, self::PRIMARY_KEY, $commonId);
    }

    /**
     * @return int
     */
    public function totalCount()
    {
        return $this->countStar($this->db, self::Q_COUNT);
    }
}
