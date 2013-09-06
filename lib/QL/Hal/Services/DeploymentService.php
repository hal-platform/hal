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
class DeploymentService
{
    use QueryTrait;

    const PRIMARY_KEY = 'DeploymentId';
    const Q_LIST = '
    SELECT
       dep.DeploymentId,
       dep.RepoId,
       env.EnvId,
       env.Name      AS Environment,
       rep.ShortName AS Repository,
       dep.ServerId,
       srv.HostName  AS ServerName,
       dep.CurStatus,
       dep.CurBranch,
       dep.CurCommit,
       dep.TargetPath
    FROM
        Deployments  AS dep                                INNER JOIN
        Servers      AS srv ON dep.ServerId = srv.ServerId INNER JOIN
        Repositories AS rep ON dep.RepoId   = rep.RepoId   INNER JOIN
        Environments AS env ON srv.EnvId    = env.EnvId
    ';
    const Q_INSERT = 'INSERT INTO Deployments (RepoId, ServerId, CurStatus, CurBranch, CurCommit, TargetPath) VALUES (:repo, :server, :status, :branch, :commit, :path)';

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
     * @param int $repo
     * @param int $server
     * @param string $status
     * @param string $branch one of 'Deploying', 'Deployed' or 'Error'
     * @param string $commit
     * @param string $path
     * @return int
     */
    public function create($repo, $server, $status, $branch, $commit, $path)
    {
        return $this->insert($this->db, self::Q_INSERT, [
            [':repo', $repo, PDO::PARAM_INT],
            [':server', $server, PDO::PARAM_INT],
            [':status', $status, PDO::PARAM_INT],
            [':branch', $branch, PDO::PARAM_INT],
            [':commit', $commit, PDO::PARAM_INT],
            [':path', $path, PDO::PARAM_INT],
        ]);
    }
}
