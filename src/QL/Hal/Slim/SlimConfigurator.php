<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use Closure;
use Slim\Slim;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is how we configure slim directly after it is instantiated.
 *
 * Please note: hooks must be passed in this form:
 * [
 *     'SLIM_HOOK_TYPE_1' => ['SERVICE_KEY_1', 'SERVICE_KEY_2'],
 *     'SLIM_HOOK_TYPE_2' => ['SERVICE_KEY_3'],
 * ]
 *
 */
class SlimConfigurator
{
    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var array
     */
    private $hooks;

    /**
     * @param array $hooks
     */
    public function __construct(ContainerInterface $di, array $hooks)
    {
        $this->di = $di;
        $this->hooks = $hooks;
    }

    /**
     * @param Slim $slim
     * @return null
     */
    public function configure(Slim $slim)
    {
        foreach ($this->hooks as $event => $hooks) {
            foreach ($hooks as $hook) {
                $slim->hook($event, $this->hookClosure($slim, $hook));
            }
        }
    }

    /**
     * Lazy loader for the actual hook services.
     *
     * @param Slim $slim
     * @param string $key
     * @return Closure
     */
    private function hookClosure(Slim $slim, $key)
    {
        return function() use ($slim, $key) {
            $service = $this->di->get($key);
            call_user_func($service, $slim);
        };
    }
}
