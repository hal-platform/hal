<?php
namespace QL\Hal\Sync;

use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\LogService;
use Swift_Message;

/**
 * Handles notifying the proper services when a sync is finished (good or bad)
 */
class NotificationService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Swift_Message
     */
    private $message;

    /**
     * @var DeploymentService
     */
    private $depService;

    /**
     * @var LogService
     */
    private $logService;

    /**
     * @var int
     */
    private $depId;

    /**
     * @var int
     */
    private $logId;

    /**
     * @var string
     */
    private $branch;

    /**
     * @var string
     */
    private $sha;

    /**
     * @param LoggerInterface $logger
     * @param Swift_Message $message
     * @param DeploymentService $depService
     * @param LogService $logService
     * @param int $depId
     * @param int $logId
     * @param string $branch
     * @param string $sha
     */
    public function __construct(
        LoggerInterface $logger,
        Swift_Message $message,
        DeploymentService $depService,
        LogService $logService,
        $depId,
        $logId,
        $branch,
        $sha
    ) {
        $this->logger = $logger;
        $this->message = $message;
        $this->depService = $depService;
        $this->logService = $logService;
        $this->depId = $depId;
        $this->logId = $logId;
        $this->branch = $branch;
        $this->sha = $sha;
    }

    /**
     * @param bool $success
     */
    public function notifySyncFinish($success)
    {
        if ($success) {
            $this->message->setSubject('[HAL9000][SUCCESS]' . $this->message->getSubject());
            $status = DeploymentService::STATUS_DEPLOYED;
        } else {
            $this->message->setSubject('[HAL9000][FAILURE]' . $this->message->getSubject());
            $status = DeploymentService::STATUS_ERROR;
            $this->logger->critical('Push failed.');
        }
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->depService->update($this->depId, $status, $this->branch, $this->sha, $now);
        $this->logService->update($this->logId, $status, $now);
    }
}
