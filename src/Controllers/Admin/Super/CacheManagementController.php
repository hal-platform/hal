<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Super;

use Predis\Client as Predis;
use Predis\Collection\Iterator\Keyspace;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class CacheManagementController implements ControllerInterface
{
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
     * @param TemplateInterface $template
     * @param Predis $predis
     * @param string $root
     */
    public function __construct(
        TemplateInterface $template,
        Predis $predis,
        $root
    ) {
        $this->template = $template;
        $this->predis = $predis;

        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $permissions = $this->getPermissions();

        $context = [
            'permissions' => $this->getPermissionTTLs($permissions),
            'doctrine_cached' => $this->getDoctrine(),
            'opcache' => $this->getOpcacheData(),
        ];

        $this->template->render($context);
    }

    /**
     * @return array
     */
    private function getPermissions()
    {
        $permissions = [];
        foreach (new Keyspace($this->predis, '*:mcp-cache:permissions:*') as $key) {
            $parts = explode(':', $key);
            $permissions[] = array_pop($parts);
        }

        return $permissions;
    }

    /**
     * @param array $permissions
     * @return array
     */
    private function getPermissionTTLs(array $permissions)
    {
        $permissionTTLs = [];

        foreach ($permissions as $key) {
            $key = sprintf('mcp-cache:permissions:%s', $key);
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
        $doctrine = [];
        foreach (new Keyspace($this->predis, '*:doctrine:*') as $key) {

            // slice namespace
            $parts = explode(':', $key);
            array_shift($parts);

            $doctrine[] = implode(':', $parts);
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
            'configuration' => $this->formatConfig($configuration['directives']),
            'scripts' => $this->formatScripts($status['scripts']),

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
        // $status['opcache_statistics']['start_time']
        // $status['opcache_statistics']['last_restart_time']
    }

    /**
     * @param array $directives
     *
     * @return array
     */
    private function formatConfig(array $directives)
    {
        return array_map(function($v) {
            return var_export($v, true);
        }, $directives);
    }

    /**
     * @param array $scripts
     *
     * @return array
     */
    private function formatScripts(array $scripts)
    {
        $formatted = [];

        // descending order
        usort($scripts, function($a, $b) {
            $a = $a['hits'];
            $b = $b['hits'];

            if ($a > $b) {
                return -1;
            } elseif ($a < $b) {
                return 11;
            }

            return 0;
        });

        $root = realpath($this->root);
        $rootlen = strlen($root);

        foreach ($scripts as $script) {

            $path = $script['full_path'];
            if (stripos($path, $root) === 0) {
                $path = substr($path, $rootlen + 1);
            }

            $formatted[] = [
                'path' => $path,
                'hits' => $script['hits'],
                'memory_consumption' => $this->formatSize($script['memory_consumption']),
            ];
        }

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
}
