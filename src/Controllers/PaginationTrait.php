<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\API\Hyperlink;

trait PaginationTrait
{
    /**
     * Get current page as an int.
     *
     * Returns null if page is invalid.
     *
     * @param array $routeParams
     *
     * @return int|null
     */
    private function getCurrentPage(array $routeParams): ?int
    {
        $page = (isset($routeParams['page'])) ? intval($routeParams['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            return null;
        }

        return $page;
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
