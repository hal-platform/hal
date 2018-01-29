<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Predis\Client as Predis;
use Predis\Collection\Iterator\Keyspace;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class CacheManagementController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var string
     */
    private $root;

    /**
     * @var string
     */
    private $keyDelimiter;

    /**
     * @param TemplateInterface $template
     * @param Predis $predis
     * @param string $root
     * @param string $keyDelimiter
     */
    public function __construct(
        TemplateInterface $template,
        Predis $predis,
        $root,
        $keyDelimiter
    ) {
        $this->template = $template;
        $this->predis = $predis;

        $this->root = $root;
        $this->keyDelimiter = $keyDelimiter;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->withTemplate($request, $response, $this->template, [
            'permissions' => $this->getPermissionTTLs(),
            'doctrine_cached' => $this->getDoctrine(),
            'opcache' => $this->getOpcacheData(),
        ]);
    }

    /**
     * @return array
     */
    private function getPermissionTTLs()
    {
        $permissionNamespace = 'mcp-cache-3.0.0:permissions';
        $keyPattern = $this->keyPattern(['*', $permissionNamespace, '*']);

        $permissionTTLs = [];

        foreach (new Keyspace($this->predis, $keyPattern) as $key) {
            $parts = explode($permissionNamespace, $key);
            $permissionKey = array_pop($parts);

            $key = sprintf('%s%s', $permissionNamespace, $permissionKey);
            $ttl = $this->predis->ttl($key);

            $permissionTTLs[$key] = $ttl;
        }

        return $permissionTTLs;
    }

    /**
     * @return array
     */
    private function getDoctrine()
    {
        $keyPattern = $this->keyPattern(['*', 'doctrine', '*']);

        $doctrine = [];
        foreach (new Keyspace($this->predis, $keyPattern) as $key) {
            // slice namespace
            $parts = explode($this->keyDelimiter, $key);
            array_shift($parts);

            $doctrine[] = implode($this->keyDelimiter, $parts);
        }

        return $doctrine;
    }

    /**
     * @return array|null
     */
    private function getOpcacheData()
    {
        if (!extension_loaded('Zend OPcache')) {
            return null;
        }

        $configuration = opcache_get_configuration();
        $status = opcache_get_status();

        $context = [
            'version' => $configuration['version']['version'],
            'scripts' => $this->formatScripts(isset($status['scripts']) ? $status['scripts'] : []),

            'enabled' => $status['opcache_enabled'],
            'cache_full' => $status['cache_full'],

            'used_memory' => $this->formatSize($status['memory_usage']['used_memory']),
            'total_memory' => $this->formatSize($configuration['directives']['opcache.memory_consumption']),

            'used_buffer' => $this->formatSize($status['interned_strings_usage']['used_memory']),
            'total_buffer' => $this->formatSize($status['interned_strings_usage']['buffer_size']),
            'count_buffer' => $status['interned_strings_usage']['number_of_strings'],

            'hits' => $status['opcache_statistics']['hits'],
            'misses' => $status['opcache_statistics']['misses'],
            'opcache_hit_rate' => round($status['opcache_statistics']['opcache_hit_rate'], 2),

            'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'],
            'cached_keys' => $status['opcache_statistics']['num_cached_keys'],
            'cached_keys_max' => $status['opcache_statistics']['max_cached_keys'],
        ];

        return $context;
    }

    /**
     * @param array $scripts
     *
     * @return array
     */
    private function formatScripts(array $scripts)
    {
        // descending order
        usort($scripts, function ($a, $b) {
            $a = $a['hits'];
            $b = $b['hits'];

            if ($a > $b) {
                return -1;
            } elseif ($a < $b) {
                return 11;
            }

            return 0;
        });

        $root = realpath($this->root) . '/';
        $rootlen = strlen($root);

        $formatted = [];
        foreach ($scripts as $script) {
            $path = $script['full_path'];
            if (stripos($path, $root) !== 0) {
                continue;
            }

            $path = './' . substr($path, $rootlen);

            $formatted[] = [
                'path' => $path,
                'hits' => $script['hits'],
                'memory_consumption' => $this->formatSize($script['memory_consumption']),
            ];
        }

        // Limit to top 100 results
        $formatted = array_slice($formatted, 0, 100);

        return $formatted;
    }

    /**
     * @param int $bytes
     *
     * @return string
     */
    private function formatSize($bytes)
    {
        if ($bytes > 1048576) {
            return sprintf('%.2f mb', $bytes / 1048576);
        }

        if ($bytes > 1024) {
            return sprintf('%.2f kb', $bytes / 1024);
        }

        return sprintf('%d bytes', $bytes);
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
