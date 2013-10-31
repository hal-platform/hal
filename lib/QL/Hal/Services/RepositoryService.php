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
    const UNIQUE_COL = 'rep.ShortName';
    const Q_LIST = 'SELECT rep.RepositoryId, rep.ShortName, arr.ShortName AS Arrangement, rep.GithubUser, rep.GithubRepo, rep.BuildCmd, rep.OwnerEmail FROM Repositories AS rep INNER JOIN Arrangements AS arr ON (rep.ArrangementId = arr.ArrangementId)';
    const Q_INSERT = 'INSERT INTO Repositories (ArrangementId, ShortName, GithubUser, GithubRepo, BuildCmd, OwnerEmail, Description) VALUES (:arrId, :name, :user, :repo, :cmd, :email, :desc)';
    const Q_COUNT = 'SELECT COUNT(*) FROM Repositories';
    const Q_LIST_BY_UNIQUE = 'SELECT RepositoryId, ShortName, GithubUser, GithubRepo, Description FROM Repositories';
    const Q_LIST_REPO_ENV_PAIRS = '
    SELECT
        rep.ShortName as RepShortName,
        env.Shortname as EnvShortName
    FROM
                   Repositories AS rep
        INNER JOIN Deployments  AS dep ON (rep.RepositoryId = dep.RepositoryId)
        INNER JOIN Servers      AS srv ON (dep.ServerId = srv.ServerId)
        INNER JOIN Environments AS env ON (srv.EnvironmentId = env.EnvironmentId)
    ';

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

    public function listRepoEnvPairs()
    {
        $stmt = $this->db->prepare(self::Q_LIST_REPO_ENV_PAIRS);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_NAMED);

        $result = array_map(function ($val) {
            return $val['RepShortName'] . '/' . $val['EnvShortName'];
        }, $result);

        $result = array_values(array_unique($result));

        $result = array_map(function ($val) {
            $val = explode('/', $val);
            $ret = [
                'RepShortName' => $val[0],
                'EnvShortName' => $val[1],
            ];
            return $ret;
        }, $result);


        return $result;
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
     * @param string $buildCommand
     * @param string $ownerEmail
     * @param string $description
     * @return int
     */
    public function create($arrId, $shortName, $githubUser, $githubRepo, $buildCommand, $ownerEmail, $description)
    {
        return $this->insert($this->db, self::Q_INSERT, [
            [':arrId', $arrId, PDO::PARAM_STR],
            [':name', $shortName, PDO::PARAM_STR],
            [':user', $githubUser, PDO::PARAM_STR],
            [':repo', $githubRepo, PDO::PARAM_STR],
            [':cmd', $buildCommand, PDO::PARAM_STR],
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
     * @param string $shortName
     * @return array|null
     */
    public function getFromName($shortName)
    {
        return $this->getByName($this->db, self::Q_LIST, self::UNIQUE_COL, $shortName);
    }

    /**
     * @return int
     */
    public function totalCount()
    {
        return $this->countStar($this->db, self::Q_COUNT);
    }

    /**
     * @param int $arrId
     * @param string $field
     * @return array
     */
    public function listByField($arrId, $field)
    {
        $ret = [];
        $query = self::Q_LIST_BY_UNIQUE . ' WHERE ' . $field . ' = :arrId ' ;
        $stmt = $this->db->prepare($query);
        $stmt ->bindValue(':arrId', $arrId, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ret[$row['RepositoryId']] = $row;
        }

       return $ret;
    }

}
