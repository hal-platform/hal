<?php

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 * Audit Log Controller
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class LogsController
{
    const MAX_PER_PAGE = 25;

    /**
     * @var Layout
     */
    private $layout;

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
     * @param Layout $layout
     * @param EntityManager $em
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EntityManager $em
    ) {
        $this->template = $template;
        $this->layout = $layout;
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
            call_user_func($notFound);
            return;
        }

        $dql = 'SELECT l FROM QL\Hal\Core\Entity\Log l ORDER BY l.recorded DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(self::MAX_PER_PAGE)
            ->setFirstResult(self::MAX_PER_PAGE * ($page-1));
        $logs = $query->getResult();

        $paginator = new Paginator($query);
        $total = count($paginator);
        $last = ceil($total / self::MAX_PER_PAGE);

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'page' => $page,
                    'last' => $last,
                    'logs' => $logs
                ]
            )
        );
    }
}
