<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use Twig_Loader_Filesystem;

/**
 * Extends and replaces the original twig environment to allow server-independent caching.
 *
 * Twig_Loader_Filesystem uses the full absolute path for determining the cache key which can change depending on the server
 * the code is pushed to.
 */
class BetterCachingTwigFilesystem extends Twig_Loader_Filesystem
{
    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        $fullPath = $this->findTemplate($name);

        foreach ($this->getPaths() as $path) {
            if (strpos($fullPath, $path) === 0) {
                $fullPath = substr($fullPath, strlen($path) + 1);
                break;
            }
        }

        return $fullPath;
    }
}
