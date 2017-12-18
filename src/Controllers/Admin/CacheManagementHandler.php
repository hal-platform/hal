<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Predis\Collection\Iterator\Keyspace;
use Predis\Client as Predis;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;

class CacheManagementHandler implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    private const DOCTRINE_CLEARED = 'Doctrine cache cleared.';
    private const PERMISSIONS_CLEARED = 'Permission cache cleared.';
    private const OPCACHE_CLEARED = 'OP cache cleared.';

    private const ERR_INVALID = 'Invalid cache specified.';

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var string
     */
    private $keyDelimiter;

    /**
     * @param Predis $predis
     * @param string $keyDelimiter
     */
    public function __construct(Predis $predis, $keyDelimiter)
    {
        $this->predis = $predis;
        $this->keyDelimiter = $keyDelimiter;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        $cacheType = $request->getParsedBody()['cache_type'] ?? '';
        $msg = $this->clearCache($cacheType);

        if ($msg) {
            $request = $this->withContext($request, [
                'clear_result' => 'success',
                'clear_message' => $msg
            ]);
        } else {
            $request = $this->withContext($request, [
                'clear_result' => 'error',
                'clear_message' => self::ERR_INVALID
            ]);
        }

        return $next($request, $response);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function clearCache($cacheType)
    {
        if ($cacheType === 'doctrine') {
            $this->deleteKeys(['*', 'doctrine', '*']);
            return self::DOCTRINE_CLEARED;
        }

        if ($cacheType === 'permissions') {
            $this->deleteKeys(['*', 'mcp-cache-3.0.0:permissions', '*']);
            return self::PERMISSIONS_CLEARED;
        }

        if ($cacheType === 'opcache' && function_exists('\opcache_reset')) {
            \opcache_reset();
            return self::OPCACHE_CLEARED;
        }

        return '';
    }

    /**
     * @param aray $parts
     *
     * @return void
     */
    private function deleteKeys(array $parts)
    {
        $keyPattern = $this->keyPattern($parts);

        $keys = [];
        foreach (new Keyspace($this->predis, $keyPattern) as $key) {

            // slice namespace
            $parts = explode($this->keyDelimiter, $key);
            array_shift($parts);

            $keys[] = implode($this->keyDelimiter, $parts);
        }

        if ($keys) {
            $this->predis->del(...$keys);
        }
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    private function keyPattern(array $parts)
    {
        return implode($this->keyDelimiter, $parts);
    }
}
