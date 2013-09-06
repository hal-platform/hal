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
class UserService
{
    use QueryTrait;

    const PRIMARY_KEY = 'CommonId';
    const Q_LIST = 'SELECT CommonId, UserName, Email, DisplayName, PictureUrl FROM Users';
    const Q_INSERT = 'INSERT INTO Users (CommonId, UserName, Email, DisplayName, PictureUrl) VALUES (:commonId, :userName, :email, :displayName, :pictureUrl)';
    const Q_UPDATE = 'UPDATE Users SET UserName = :userName, Email = :email, DisplayName = :displayName, PictureUrl = :pictureUrl WHERE CommonId = :commonId';

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
     * Creates a new User record
     *
     * Note: does not use QueryTrait->insert() because there is no
     * auto-incrementing primary key for this particular table.
     *
     * @param int $commonId
     * @param string $userName
     * @param string $email
     * @param string $displayName
     * @param string $pictureUrl
     * @return null
     */
    public function create($commonId, $userName, $email, $displayName, $pictureUrl)
    {
        $stmt = $this->db->prepare(self::Q_INSERT);
        $stmt->bindValue(':commonId', $commonId, PDO::PARAM_INT);
        $stmt->bindValue(':userName', $userName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':displayName', $displayName, PDO::PARAM_STR);
        $stmt->bindValue(':pictureUrl', $pictureUrl, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @param int $commonId
     * @param string $userName
     * @param string $email
     * @param string $displayName
     * @param string $pictureUrl
     * @return null
     */
    public function update($commonId, $userName, $email, $displayName, $pictureUrl)
    {
        $stmt = $this->db->prepare(self::Q_UPDATE);
        $stmt->bindValue(':commonId', $commonId, PDO::PARAM_INT);
        $stmt->bindValue(':userName', $userName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':displayName', $displayName, PDO::PARAM_STR);
        $stmt->bindValue(':pictureUrl', $pictureUrl, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @param int $commonId
     * @return array|null
     */
    public function getById($commonId)
    {
        return $this->selectOne($this->db, self::Q_LIST, self::PRIMARY_KEY, $commonId);
    }
}
