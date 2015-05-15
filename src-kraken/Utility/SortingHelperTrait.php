<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Utility;

use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;

trait SortingHelperTrait
{
    /**
     * @return callable
     */
    public function environmentSorter()
    {
        $order = [
            'dev' => 0,
            'test' => 1,
            'beta' => 2,
            'prod' => 3
        ];

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
    public function targetSorter()
    {
        $order = [
            'dev' => 0,
            'test' => 1,
            'beta' => 2,
            'prod' => 3
        ];

        return function(Target $a, Target $b) use ($order) {

            $aName = strtolower($a->environment()->name());
            $bName = strtolower($b->environment()->name());

            $aOrder = isset($order[$aName]) ? $order[$aName] : 999;
            $bOrder = isset($order[$bName]) ? $order[$bName] : 999;

            if ($aOrder === $bOrder) {
                return 0;
            }

            return ($aOrder > $bOrder);
        };
    }
}
