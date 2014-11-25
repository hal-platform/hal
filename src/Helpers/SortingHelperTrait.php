<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

trait SortingHelperTrait
{
    /**
     * @return callable
     */
    public function deploymentSorter()
    {
        $regex = '#' .
            '([a-z]{1,10}[\d]{1,2})' . // some letters followed by numbers. This is the datacenter identifier.
            '([a-z]{1,10})' . // some letters. This usually identifiers the tier or network
            '([\d]{1,2})' . // 1-2 digits
            '#';

        return function($a, $b) use ($regex) {
            $serverA = $a->getServer()->getName();
            $serverB = $b->getServer()->getName();

            // same server
            if ($serverA === $serverB) {
                return strcmp($a->getPath(), $b->getPath());
            }

            $isA = preg_match($regex, $serverA, $matchesA);
            $isB = preg_match($regex, $serverB, $matchesB);

            // One does not follow schema, move to bottom
            if (!$isA || !$isB) {
                return ($isA) ? 1 : -1;
            }

            // both match
            $parsedA = [
                'datacenter' => $matchesA[1],
                'tier' => $matchesA[2],
                'server' => $matchesA[3]
            ];

            $parsedB = [
                'datacenter' => $matchesB[1],
                'tier' => $matchesB[2],
                'server' => $matchesB[3]
            ];

            // datacenters different, compare datacenter
            if ($parsedA['datacenter'] !== $parsedB['datacenter']) {
                return strcmp($parsedA['datacenter'], $parsedB['datacenter']);
            }

            // same datacenter, tier different, compare tier
            if ($parsedA['tier'] !== $parsedB['tier']) {
                return strcmp($parsedA['tier'], $parsedB['tier']);
            }

            // same datacenter, same tier, compare server
            if ($parsedA['server'] !== $parsedB['server']) {
                return ($parsedA['server'] > $parsedB['server']) ? 1 : -1;
            }
        };
    }
}
