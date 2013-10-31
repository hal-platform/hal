<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use DateTime;
use DateTimeZone;
use PDO;

/**
 * @api
 */
class DeploymentService
{
    use QueryTrait;

    const STATUS_DEPLOYING = 'Deploying';
    const STATUS_DEPLOYED = 'Deployed';
    const STATUS_ERROR = 'Error';

    const PRIMARY_KEY = 'DeploymentId';
    const Q_LIST = '
    SELECT
       dep.DeploymentId,
       dep.RepositoryId,
       dep.ServerId,
       env.EnvironmentId,
       env.ShortName      AS Environment,
       rep.ShortName      AS Repository,
       rep.GithubUser,
       rep.GithubRepo,
       srv.HostName       AS HostName,
       dep.CurStatus,
       dep.CurBranch,
       LOWER(HEX(dep.CurCommit)) AS CurCommit,
       dep.LastPushed,
       dep.TargetPath
    FROM
        Deployments  AS dep                                          INNER JOIN
        Servers      AS srv ON dep.ServerId      = srv.ServerId      INNER JOIN
        Repositories AS rep ON dep.RepositoryId  = rep.RepositoryId  INNER JOIN
        Environments AS env ON srv.EnvironmentId = env.EnvironmentId
    ';
    const Q_LIST_ORDER = 'ORDER BY rep.ShortName ASC, env.DispOrder, srv.HostName ASC';
    const Q_INSERT = 'INSERT INTO Deployments (RepositoryId, ServerId, CurStatus, CurBranch, CurCommit, LastPushed, TargetPath) VALUES (:repo, :server, :status, :branch, UNHEX(:commit), :lastpushed, :path)';

    const Q_LIST_FOR_REPO_CLAUSE = ' dep.RepositoryId = :id ';
    const Q_DELETE = 'DELETE FROM Deployments WHERE DeploymentId = :id';
    const Q_UPDATE = 'UPDATE Deployments SET CurStatus = :status, CurBranch = :branch, CurCommit = UNHEX(:commit), LastPushed = :pushed WHERE DeploymentId = :id';
    const Q_EXISTS = 'SELECT COUNT(*) FROM Deployments WHERE RepositoryId = :repo AND ServerId = :server';

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

    public function listAllByRepoId($repoId)
    {
        $ret = [];
        $query = self::Q_LIST . ' WHERE ' . self::Q_LIST_FOR_REPO_CLAUSE . self::Q_LIST_ORDER;
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $repoId, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['LastPushed'] === '0000-00-00 00:00:00') {
                $row['LastPushed'] = null;
            } else {
                $row['LastPushed'] = new DateTime($row['LastPushed'], new DateTimeZone('UTC'));
            }

            $ret[$row[self::PRIMARY_KEY]] = $row;
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function listAll()
    {
        $result = $this->selectAll($this->db, self::Q_LIST . self::Q_LIST_ORDER, self::PRIMARY_KEY);
        foreach ($result as &$item) {
            if ($item['LastPushed'] === '0000-00-00 00:00:00') {
                $item['LastPushed'] = null;
            } else {
                $item['LastPushed'] = new DateTime($item['LastPushed'], new DateTimeZone('UTC'));
            }
        }
        return $result;
    }

    /**
     * @param int $repo
     * @param int $server
     * @param string $status
     * @param string $branch one of 'Deploying', 'Deployed' or 'Error'
     * @param string $commit
     * @param string $path
     * @param DateTime|null $lastPushed
     * @return int
     */
    public function create($repo, $server, $status, $branch, $commit, $path, DateTime $lastPushed = null)
    {
        if ($this->exists($repo, $server)) {
            return 0;
        }

        if ($lastPushed === null) {
            $lastPushed = '0000-00-00 00:00:00';
        } else {
            $lastPushed = $lastPushed->format('Y-m-d H:i:s');
        }

        return $this->insert($this->db, self::Q_INSERT, [
            [':repo', $repo, PDO::PARAM_INT],
            [':server', $server, PDO::PARAM_INT],
            [':status', $status, PDO::PARAM_STR],
            [':branch', $branch, PDO::PARAM_STR],
            [':commit', $commit, PDO::PARAM_STR],
            [':lastpushed', $lastPushed, PDO::PARAM_STR],
            [':path', $path, PDO::PARAM_STR],
        ]);
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $item = $this->selectOne($this->db, self::Q_LIST, self::PRIMARY_KEY, $id);
        if (!$item) {
            return $item;
        }
        if ($item['LastPushed'] === '0000-00-00 00:00:00') {
            $item['LastPushed'] = null;
        } else {
            $item['LastPushed'] = new DateTime($item['LastPushed'], new DateTimeZone('UTC'));
        }
        return $item;
    }

    /**
     *  Remove a deployment by ID
     *
     *  @param $id
     */
    public function remove($id)
    {
        $stmt = $this->db->prepare(self::Q_DELETE);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    /**
     * @param $id
     * @param $status
     * @param $branch
     * @param $commit
     * @param DateTime $dateTime
     */
    public function update($id, $status, $branch, $commit, DateTime $dateTime = null)
    {
        if (!$dateTime) {
            $dateTime = '0000-00-00 00:00:00';
        } else {
            $dateTime = $dateTime->format('Y-m-d H:i:s');
        }

        $stmt = $this->db->prepare(self::Q_UPDATE);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':branch', $branch, PDO::PARAM_STR);
        $stmt->bindValue(':commit', $commit, PDO::PARAM_STR);
        $stmt->bindValue(':pushed', $dateTime, PDO::PARAM_STR);

        $stmt->execute();
    }

    /**
     *  Check if a repo:server pair already exists
     *
     *  @param $repo
     *  @param $server
     *  @return bool
     */
    protected function exists($repo, $server)
    {
        $stmt = $this->db->prepare(self::Q_EXISTS);
        $stmt->bindValue(':repo', $repo);
        $stmt->bindValue(':server', $server);
        $stmt->execute();

        $result = $stmt->fetch();

        if ($result[0]) {
            return true;
        } else {
            return false;
        }
    }
}
