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
class RepositoryService
{
    use QueryTrait;

    const PRIMARY_KEY = 'RepositoryId';
    const UNIQUE_COL = 'ArrangementId';
    const Q_LIST = 'SELECT RepositoryId, ShortName, GithubUser, GithubRepo, OwnerEmail FROM Repositories';
    const Q_INSERT = 'INSERT INTO Repositories (ArrangementId, ShortName, GithubUser, GithubRepo, OwnerEmail, Description) VALUES (:arrId, :name, :user, :repo, :email, :desc)';
    const Q_COUNT = 'SELECT COUNT(*) FROM Repositories';
    const Q_LIST_BY_ARRANGEMENT = 'SELECT RepositoryId, ShortName, GithubUser, GithubRepo, Description FROM Repositories';
    
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
     * @param string $arrId
     * @param string $shortName
     * @param string $githubUser
     * @param string $githubRepo
     * @param string $ownerEmail
     * @param string $description
     * @return int
     */
    public function create($arrId, $shortName, $githubUser, $githubRepo, $ownerEmail, $description)
    {
        return $this->insert($this->db, self::Q_INSERT, [
            [':arrId', $arrId, PDO::PARAM_STR],
            [':name', $shortName, PDO::PARAM_STR],
            [':user', $githubUser, PDO::PARAM_STR],
            [':repo', $githubRepo, PDO::PARAM_STR],
            [':email', $ownerEmail, PDO::PARAM_STR],
            [':desc', $description, PDO::PARAM_STR],
        ]);
    }

    /**
     * @param int $repoId
     * @return array|null
     */
    public function getById($repoId)
    {
        return $this->selectOne($this->db, self::Q_LIST, self::PRIMARY_KEY, $repoId);
    }

    /**
     * @return int
     */
    public function totalCount()
    {
        return $this->countStar($this->db, self::Q_COUNT);
    }

    /**
     * @return array
     */
    public function listByArrangement($arrId)
    {
        $ret = [];
        $query = self::Q_LIST_BY_ARRANGEMENT . ' WHERE ' . self::UNIQUE_COL . ' = :arrId ' ;
        $stmt = $this->db->prepare($query);
        $stmt ->bindValue(':arrId', $arrId, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ret[$row['RepositoryId']] = $row;
        }

       return $ret;
    }

}
