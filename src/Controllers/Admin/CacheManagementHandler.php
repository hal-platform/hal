<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\ORM\Configuration;
use Predis\Client as Predis;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

class CacheManagementHandler implements MiddlewareInterface
{
    const DOCTRINE_CLEARED = 'Doctrine cache cleared.';
    const PERMISSIONS_CLEARED = 'Permission cache cleared.';
    const OPCACHE_CLEARED = 'OP cache cleared.';

    const ERR_INVALID = 'Invalid cache specified.';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Configuration
     */
    private $doctrineConfig;

    /**
     * @type Predis
     */
    private $predis;

    /**
     * @param Request $request
     * @param Response $response
     * @param Session $session
     * @param Url $url
     * @param Configuration $doctrineConfig
     * @param Predis $predis
     */
    public function __construct(
        Request $request,
        Response $response,
        Session $session,
        Url $url,
        Configuration $doctrineConfig,
        Predis $predis
    ) {
        $this->request = $request;
        $this->response = $response;

        $this->session = $session;
        $this->url = $url;

        $this->doctrineConfig = $doctrineConfig;
        $this->predis = $predis;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $msg = '';

        if ($this->request->post('cache_type') === 'doctrine') {

            $this->clearDoctrine();
            $msg = self::DOCTRINE_CLEARED;

        } elseif ($this->request->post('cache_type') === 'permissions') {

            $this->clearPermissions();
            $msg = self::PERMISSIONS_CLEARED;

        } elseif ($this->request->post('cache_type') === 'opcache') {

            if (function_exists('opcache_reset')) {
                opcache_reset();
                $msg = self::OPCACHE_CLEARED;
            }
        }

        if ($msg) {
            $this->session->flash($msg, 'success');
        } else {
            $this->session->flash(self::ERR_INVALID, 'error');
        }

        $this->url->redirectFor('admin.super.caches', [], [], 303);
    }

    /**
     * @return void
     */
    private function clearPermissions()
    {
        $permissions = $this->predis->keys('mcp-cache:permissions:*');

        $permissions = array_map(function(&$key) {
            $namespacePosition = strpos($key, ':');
            if ($namespacePosition === false) {
                return $key;
            } else {
                return substr($key, $namespacePosition + 1);
            }
        }, $permissions);


        if ($permissions) {
            call_user_func_array([$this->predis, 'del'], $permissions);
        }
    }
    /**
     * @return void
     */
    private function clearDoctrine()
    {
        if ($cache = $this->doctrineConfig->getQueryCacheImpl()) {
            $cache->deleteAll();
        }

        if ($cache = $this->doctrineConfig->getHydrationCacheImpl()) {
            $cache->deleteAll();
        }

        if ($cache = $this->doctrineConfig->getMetadataCacheImpl()) {
            $cache->deleteAll();
        }
    }
}
