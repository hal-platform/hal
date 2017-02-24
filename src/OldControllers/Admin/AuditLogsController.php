<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\AuditLog;
use QL\Hal\Core\Repository\AuditLogRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class AuditLogsController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var AuditLogRepository
     */
    private $auditRepo;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->auditRepo = $em->getRepository(AuditLog::CLASS);

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $logs = $this->auditRepo->getPagedResults(self::MAX_PER_PAGE, ($page-1));

        // 404, no logs
        if (count($logs) < 1) {
            return call_user_func($this->notFound);
        }

        // Get current page count
        // Must manually calculate this, as count() will give MAX RESULTS.
        $thisPageCount = 0;
        foreach ($logs as $log) {
            $thisPageCount++;
        }

        $total = count($logs);
        $last = ceil($total / self::MAX_PER_PAGE);

        $this->template->render([
            'page' => $page,
            'last' => $last,

            'logs' => $logs
        ]);
    }
}