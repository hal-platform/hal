<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Type\EnumType\ServerEnum;

trait SortingHelperTrait
{
    private $sortingHelperEnvironmentOrder = [
        'dev' => 0,
        'test' => 1,
        'beta' => 2,
        'prod' => 3
    ];

    /**
     * @return callable
     */
    public function environmentSorter()
    {
        $order = $this->sortingHelperEnvironmentOrder;

        return function(Environment $a, Environment $b) use ($order) {

            $aName = strtolower($a->name());
            $bName = strtolower($b->name());

            $aOrder = isset($order[$aName]) ? $order[$aName] : 999;
            $bOrder = isset($order[$bName]) ? $order[$bName] : 999;

            if ($aOrder === $bOrder) {
                return 0;
            }

            return ($aOrder > $bOrder);
        };
    }

    /**
     * @return callable
     */
    public function deploymentSorter()
    {
        $serverSorter = $this->serverSorter();

        return function($a, $b) use ($serverSorter) {
            $serverA = $a->getServer();
            $serverB = $b->getServer();

            // same server
            if ($serverA->getId() === $serverB->getId()) {
                return strcmp($a->getPath(), $b->getPath());
            }

            return $serverSorter($serverA, $serverB);
        };
    }

    /**
     * @return callable
     */
    public function serverSorter()
    {
        $regex = '#' .
            '([a-z]{1,10}[\d]{1,2})' . // some letters followed by numbers. This is the datacenter identifier.
            '([a-z]{1,10})' . // some letters. This usually identifiers the tier or network
            '([\d]{1,2})' . // 1-2 digits
            '([a-z]*)' . // random letters, because thats apparently a thing now.
            '#';

        return function($a, $b) use ($regex) {
            $serverA = $a->getName();
            $serverB = $b->getName();

            // same server
            if ($a->getId() === $b->getId()) {
                return 0;
            }

            // put rsync at top
            if ($a->getType() === ServerEnum::TYPE_RSYNC xor $b->getType() === ServerEnum::TYPE_RSYNC) {
                return ($a->getType() === ServerEnum::TYPE_RSYNC) ? -1 : 1;
            }

            // put eb above ec2
            if ($a->getType() !== ServerEnum::TYPE_RSYNC && $b->getType() !== ServerEnum::TYPE_RSYNC) {
                return ($a->getType() === ServerEnum::TYPE_EB) ? -1 : 1;
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
                'server' => $matchesA[3],
                'suffix' => $matchesA[4]
            ];

            $parsedB = [
                'datacenter' => $matchesB[1],
                'tier' => $matchesB[2],
                'server' => $matchesB[3],
                'suffix' => $matchesA[4]
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

            // same datacenter, same tier, same server, compare bullshit letters at the very end
            if ($parsedA['suffix'] !== $parsedB['suffix']) {
                return strcmp($parsedA['suffix'], $parsedB['suffix']);
            }

            // fall back to just straight comparison
            return strcmp($serverA, $serverB);
        };
    }
}
