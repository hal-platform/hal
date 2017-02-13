<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Utility;

trait ReleaseSortingTrait
{
    /**
     * @return callable
     */
    public function releaseSorter()
    {
        return function($a, $b) {
            $matchesA = $this->semver($a['name']);
            $matchesB = $this->semver($b['name']);

            // If both are not semver, bump non-semver to bottom
            if ($matchesA === false xor $matchesB === false) {
                if ($matchesA === false) {
                    return 1;
                }

                return -1;
            }

            // both non-semver
            if ($matchesA === false && $matchesB === false) {
                return strcasecmp($a['name'], $b['name']);
            }

            // both sem-ver

            // special case for when version number is the same (1.0-beta1 vs 1.0-alpha1)
            if ($matchesA[1] == $matchesB[1]) {
                $textA = (isset($matchesA[5])) ? $matchesA[5] : '';
                $textB = (isset($matchesB[5])) ? $matchesB[5] : '';
                $numA = (isset($matchesA[6])) ? $matchesA[6] : 0;
                $numB = (isset($matchesB[6])) ? $matchesB[6] : 0;

                // special case when release type is the same (1.0-rc1 vs 1.0-rc2)
                if ($textA == $textB) {
                    return strcasecmp($numB, $numA);
                }

                return strcasecmp($textB, $textA);
            }

            return version_compare($matchesB[1], $matchesA[1]);
        };
    }

    /**
     * Check to see if a string is *close* to being valid semver. Checking for actual spec compliance turns out to be
     * too restrictive in most cases and won't match what users will expect it to.
     *
     * For example, this will catch things like 1.0, 1, 1.0-rc1, 1.0.0.0, and v1.0 despite those values not being
     * compliant with the semver spec.
     *
     * @param $value
     *
     * @return false|string
     */
    private function semver($value)
    {
        return (preg_match('#^v?(([0-9]+\.?)+)(-(([a-zA-Z]+)([0-9]+)))?#', $value, $matches) > 0) ? $matches : false;
    }
}
