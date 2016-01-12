<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Super;

use Predis\Collection\Iterator\Keyspace;
use Predis\Client as Predis;
use QL\Hal\Flasher;
use QL\Panthor\MiddlewareInterface;
use Slim\Http\Request;

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
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Predis
     */
    private $predis;

    /**
     * @param Request $request
     * @param Flasher $flasher
     * @param Predis $predis
     */
    public function __construct(
        Request $request,
        Flasher $flasher,
        Predis $predis
    ) {
        $this->request = $request;
        $this->flasher = $flasher;
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
            $this->flasher->withFlash($msg, 'success');
        } else {
            $this->flasher->withFlash(self::ERR_INVALID, 'error');
        }

        $this->flasher->load('admin.super.caches');
    }

    /**
     * @return void
     */
    private function clearPermissions()
    {
        $keys = [];
        foreach (new Keyspace($this->predis, '*:mcp-cache:permissions:*') as $key) {

            // slice namespace
            $parts = explode(':', $key);
            array_shift($parts);

            $keys[] = implode(':', $parts);
        }

        if ($keys) {
            call_user_func_array([$this->predis, 'del'], $keys);
        }
    }

    /**
     * @return void
     */
    private function clearDoctrine()
    {
        $keys = [];
        foreach (new Keyspace($this->predis, '*:doctrine:*') as $key) {

            // slice namespace
            $parts = explode(':', $key);
            array_shift($parts);

            $keys[] = implode(':', $parts);
        }

        if ($keys) {
            call_user_func_array([$this->predis, 'del'], $keys);
        }
    }
}
