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
class LogService
{
    use QueryTrait;

    const PRIMARY_KEY = 'PushLogId';
    const Q_LIST = '
    SELECT
        PushLogId,
        PushStart,
        PushEnd,
        PushStatus,
        PushCommonId,
        PushUserName,
        PushRepo,
        PushBranch,
        LOWER(HEX(CommitSha)) AS CommitSha,
        Environment,
        TargetServer,
        TargetPath
    FROM
        PushLogs';

    const Q_INSERT = '
    INSERT INTO PushLogs (
        PushStart,
        PushEnd,
        PushCommonId,
        PushUserName,
        PushRepo,
        PushBranch,
        CommitSha,
        Environment,
        TargetServer,
        TargetPath
    ) VALUES (
        :startTime,
        \'0000-00-00 00:00:00\',
        :commonId,
        :userName,
        :repoName,
        :branchName,
        UNHEX(:commit),
        :envName,
        :hostName,
        :targetPath
    )';

    const Q_UPDATE = '
    UPDATE PushLogs SET
        PushEnd = :finished,
        PushStatus = :status
    WHERE
        PushLogId = :id
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

    /**
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $logEntry = $this->selectOne($this->db, self::Q_LIST, self::PRIMARY_KEY, $id);
        $logEntry['PushStart'] = new DateTime($logEntry['PushStart'], new DateTimeZone('UTC'));
        if ($logEntry['PushEnd'] !== '0000-00-00 00:00:00') {
            $logEntry['PushEnd'] = new DateTime($logEntry['PushEnd'], new DateTimeZone('UTC'));
        } else {
            $logEntry['PushEnd'] = null;
        }

        return $logEntry;
    }

    /**
     * @param string $shortName
     * @return array|null
     */
    public function getByRepo($shortName)
    {
        $ret = [];
        $q_field = 'PushRepo';
        $query = self::Q_LIST . ' WHERE ' . $q_field . ' = :name ';
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':name', $shortName, PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ret[$row['PushLogId']] = $row;
        }
       # $logEntry['PushStart'] = new DateTime($logEntry['PushStart'], new DateTimeZone('UTC'));
       # if ($logEntry['PushEnd'] !== '0000-00-00 00:00:00') {
       #     $logEntry['PushEnd'] = new DateTime($logEntry['PushEnd'], new DateTimeZone('UTC'));
       # } else {
       #     $logEntry['PushEnd'] = null;
       # }

        return $ret;
    }

    /**
     * @param DateTime $startTime
     * @param int $commonId
     * @param string $userName
     * @param string $repoName
     * @param string $branchName
     * @param string $commit
     * @param string $envName
     * @param string $hostName
     * @param string $targetPath
     * @return int
     */
    public function create(
        DateTime $startTime,
        $commonId,
        $userName,
        $repoName,
        $branchName,
        $commit,
        $envName,
        $hostName,
        $targetPath
    ) {
        return $this->insert($this->db, self::Q_INSERT, [
            [':startTime', $startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR],
            [':commonId', $commonId, PDO::PARAM_INT],
            [':userName', $userName, PDO::PARAM_STR],
            [':repoName', $repoName, PDO::PARAM_STR],
            [':branchName', $branchName, PDO::PARAM_STR],
            [':commit', $commit, PDO::PARAM_STR],
            [':envName', $envName, PDO::PARAM_STR],
            [':hostName', $hostName, PDO::PARAM_STR],
            [':targetPath', $targetPath, PDO::PARAM_STR],
        ]);
    }

    /**
     * @param int $id
     * @param string $status
     * @param DateTime $finished
     */
    public function update($id, $status, DateTime $finished)
    {
        $stmt = $this->db->prepare(self::Q_UPDATE);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':finished', $finished->format('Y-m-d H:i:s'));
        $stmt->execute();
    }
}
