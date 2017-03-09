<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\AuditLog;
use QL\Hal\Core\Repository\AuditLogRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class AuditHistoryController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    private const MAX_PER_PAGE = 25;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var AuditLogRepository
     */
    private $auditRepo;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, callable $notFound)
    {
        $this->template = $template;
        $this->auditRepo = $em->getRepository(AuditLog::class);

        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return ($this->notFound)($request, $response);
        }

        $events = $this->auditRepo->getPagedResults(self::MAX_PER_PAGE, ($page-1));

        $total = count($events);
        $last = ceil($total / self::MAX_PER_PAGE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'events' => $events
        ]);
    }
}