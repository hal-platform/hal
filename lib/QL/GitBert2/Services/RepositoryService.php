<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\GitBert2\Services;

use PDO;

/**
 * @api
 */
class RepositoryService
{
    use QueryTrait;

    const PRIMARY_KEY = 'RepositoryId';
    const Q_LIST = 'SELECT RepositoryId, ShortName, GithubUser, GithubRepo, OwnerEmail FROM Repositories';
    const Q_INSERT = 'INSERT INTO Repositories (ShortName, GithubUser, GithubRepo, OwnerEmail, Description) VALUES (:name, :user, :repo, :email, :desc)';

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
     * @param string $githubUser
     * @param string $githubRepo
     * @param string $ownerEmail
     * @param string $description
     * @param $shortName
     * @return int
     */
    public function create($shortName, $githubUser, $githubRepo, $ownerEmail, $description)
    {
        return $this->insert($this->db, self::Q_INSERT, [
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
}
