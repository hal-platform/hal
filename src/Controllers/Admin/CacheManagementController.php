<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\ORM\Configuration;
use Predis\Client as Predis;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class CacheManagementController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Configuration
     */
    private $doctrineConfig;

    /**
     * @type Predis
     */
    private $predis;

    /**
     * @param TemplateInterface $template
     * @param Configuration $doctrineConfig
     * @param Predis $predis
     */
    public function __construct(
        TemplateInterface $template,
        Configuration $doctrineConfig,
        Predis $predis
    ) {
        $this->template = $template;
        $this->doctrineConfig = $doctrineConfig;
        $this->predis = $predis;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $context = [];

        # clear doctrine
        if ($request->get('clear_doctrine')) {
            $context['doctrine_status'] = [
                'Query' => $this->clearDoctrine('getQueryCacheImpl'),
                'Hydration' => $this->clearDoctrine('getHydrationCacheImpl'),
                'Metadata' => $this->clearDoctrine('getMetadataCacheImpl')
            ];
        }

        # clear permissions
        $permissions = $this->getPermissions();
        if ($request->get('clear_permissions') && $permissions) {
            call_user_func_array([$this->predis, 'del'], $permissions);

            $context['permission_status'] = array_map(function($v) {
                $key = stristr($v, 'mcp-cache:permissions:');
                if (0 === strpos($key, 'mcp-cache:permissions:')) {
                    $key = substr($key, 22);
                }

                return $key;
            }, $permissions);

        } else {
            # list permissions and ttl
            $context['permissions'] = $this->getPermissionTTLs($permissions);
        }

        $rendered = $this->template->render($context);

        $response->setBody($rendered);
    }

    /**
     * @param string $accessor
     *
     * @return string
     */
    private function clearDoctrine($accessor)
    {
        if (!$cache = $this->doctrineConfig->$accessor()) {
            return 'Cache missing.';
        }

        $cache->deleteAll();
        return sprintf('"%s" reset.', get_class($cache));
    }

    /**
     * @return array
     */
    private function getPermissions()
    {
        $permissions = $this->predis->keys('mcp-cache:permissions:*');

        return array_map(function(&$key) {
            $namespacePosition = strpos($key, ':');
            if ($namespacePosition === false) {
                return $key;
            } else {
                return substr($key, $namespacePosition + 1);
            }
        }, $permissions);
    }

    /**
     * @param array $permissions
     * @return array
     */
    private function getPermissionTTLs(array $permissions)
    {
        $permissionTTLs = [];

        foreach ($permissions as $key) {
            $ttl = $this->predis->ttl($key);

            $key = stristr($key, 'mcp-cache:permissions:');
            if (0 === strpos($key, 'mcp-cache:permissions:')) {
                $key = substr($key, 22);
            }

            $permissionTTLs[$key] = $ttl;
        }

        return $permissionTTLs;
    }
}
