<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Hal\UI\API\Hyperlink;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


trait PaginationTrait
{
    /**
     * Get current page as an int.
     *
     * Returns null if page is invalid.
     *
     * @param ServerRequestInterface $request
     *
     * @return int
     */
    private function getCurrentPage(ServerRequestInterface $request): int
    {
        if (!$route = $request->getAttribute('route')) {
            return 1;
        }

        $params = $route->getArguments();

        $page = (isset($params['page'])) ? intval($params['page']) : 1;

        // invalid page
        if ($page < 1) {
            return 1;
        }

        return $page;
    }

    /**
     * Destructure actual array of entities from a Paginator.
     *
     * @param Paginator $paginator
     *
     * @return array
     */
    private function getEntitiesForPage(Paginator $paginator): array
    {
        $entities = [];

        foreach ($paginator as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * @param array $items
     * @param int $pageSize
     *
     * @return int
     */
    private function getLastPage($items, int $pageSize): int
    {
        $total = count($items);
        return ceil($total / $pageSize);
    }

    /**
     * @param string $pagedRouteName
     * @param int $current
     * @param int $total
     * @param int $pageMax
     * @param array $routeParams
     *
     * @return array
     */
    private function buildPaginationLinks(
        string $routeName,
        int $current,
        int $total,
        int $pageMax,
        array $routeParams = []
    ): array {
        $links = [];

        $prev = $current - 1;
        $next = $current + 1;
        $last = ceil($total / $pageMax);

        if ($current > 1) {
            $links['prev'] = new Hyperlink([$routeName, $routeParams + ['page' => $prev]]);
        }

        if ($next <= $last) {
            $links['next'] = new Hyperlink([$routeName, $routeParams + ['page' => $next]]);
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = new Hyperlink([$routeName, $routeParams + ['page' => '1']]);
        }

        if ($last > 1) {
            $links['last'] = new Hyperlink([$routeName, $routeParams + ['page' => $last]]);
        }

        return $links;
    }
}
