<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Hal\Core\Entity\Repository\AuditLogRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AuditLogsController
{
    const MAX_PER_PAGE = 25;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type AuditLogRepository
     */
    private $auditRepo;

    /**
     * @param TemplateInterface $template
     * @param AuditLogRepository $auditRepository
     */
    public function __construct(TemplateInterface $template, AuditLogRepository $auditRepository)
    {
        $this->template = $template;
        $this->auditRepo = $auditRepository;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $page = (isset($params['page'])) ? $params['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return $notFound();
        }

        $logs = $this->auditRepo->getPagedResults(self::MAX_PER_PAGE, ($page-1));

        // 404, no logs
        if (count($logs) < 1) {
            return $notFound();
        }

        // Get current page count
        // Must manually calculate this, as count() will give MAX RESULTS.
        $thisPageCount = 0;
        foreach ($logs as $log) {
            $thisPageCount++;
        }

        $total = count($logs);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,

            'logs' => $logs
        ]);

        $response->setBody($rendered);
    }
}
