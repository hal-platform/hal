<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use QL\Hal\Core\Entity\Repository\AuditLogRepository;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class AuditLogsController implements ControllerInterface
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
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param AuditLogRepository $auditRepository
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        AuditLogRepository $auditRepository,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->auditRepo = $auditRepository;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
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

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,

            'logs' => $logs
        ]);

        $this->response->setBody($rendered);
    }
}
