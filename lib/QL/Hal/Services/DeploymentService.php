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
    ORDER BY
        rep.ShortName ASC,
        srv.HostName ASC,
        env.DispOrder ASC
    ';
    const Q_INSERT = 'INSERT INTO Deployments (RepositoryId, ServerId, CurStatus, CurBranch, CurCommit, LastPushed, TargetPath) VALUES (:repo, :server, :status, :branch, UNHEX(:commit), :lastpushed, :path)';

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
        $result = $this->selectAll($this->db, self::Q_LIST, self::PRIMARY_KEY);
        foreach ($result as $item) {
            if ($item['LastPushed'] === '0000-00-00 00:00:00') {
                $item['LastPushed'] = null;
            } else {
                $item['LastPushed'] = new DateTime('LastPushed', new DateTimeZone('UTC'));
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
}
