<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class LogsController
{
    const MAX_PER_PAGE = 25;

    /**
     * @var Twig_Template
     */
    private $template;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param Twig_Template $template
     * @param EntityManager $em
     */
    public function __construct(Twig_Template $template, EntityManager $em)
    {
        $this->template = $template;
        $this->em = $em;
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

        if ($page < 1) {
            return call_user_func($notFound);
        }

        $dql = 'SELECT l FROM QL\Hal\Core\Entity\AuditLog l ORDER BY l.recorded DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(self::MAX_PER_PAGE)
            ->setFirstResult(self::MAX_PER_PAGE * ($page-1));
        $logs = $query->getResult();

        $paginator = new Paginator($query);
        $total = count($paginator);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,
            'logs' => $logs
        ]);
    }
}
