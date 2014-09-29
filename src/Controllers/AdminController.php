<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\ORM\Configuration;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @todo change this name
 */
class AdminController
{
    /**
     * @var Configuration
     */
    private $doctrineConfig;

    /**
     * @param Configuration $doctrineConfig
     */
    public function __construct(Configuration $doctrineConfig)
    {
        $this->doctrineConfig = $doctrineConfig;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $body = 'No action';

        if ($request->get('clear_doctrine_cache')) {
            $cacheStatus = [];

            $cacheStatus = array_merge($cacheStatus, $this->clearCache('Query', 'getQueryCacheImpl'));
            $cacheStatus = array_merge($cacheStatus, $this->clearCache('Hydration', 'getHydrationCacheImpl'));
            $cacheStatus = array_merge($cacheStatus, $this->clearCache('Metadata', 'getMetadataCacheImpl'));

            $cacheStatus[] = 'Protip: Smash the reload button to make sure the cache is cleared on ALL SERVERS!';
            $body = implode("\n\n====================\n\n", $cacheStatus);
        }

        $response->headers['Content-Type'] = 'text/plain';
        $response->body($body);
    }

    /**
     * @param string $type
     * @param string $accessor
     * @return array
     */
    private function clearCache($type, $accessor)
    {
        if (!$cache = $this->doctrineConfig->$accessor()) {
            $body = sprintf("%s Cache Type:\nMissing", $type);
            return [$body];
        }

        $body = sprintf("%s Cache Type:\n%s\n\n", $type, get_class($cache));

        $body .= sprintf(" > %s Cache reset!\n", $type);

        $cache->deleteAll();

        return [$body];
    }
}
