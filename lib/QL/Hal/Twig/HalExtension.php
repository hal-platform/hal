<?php
# lib/QL/Hal/Twig/HalExtension.php

namespace QL\Hal\Twig;

use Twig_Extension;
use Twig_SimpleFunction;
use QL\Hal\PushPermissionService;

/**
 *  Twig Extension for HAL9000
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    private $permissions;

    /**
     *  Constructor
     *
     *  @param PushPermissionService $permissions
     */
    public function __construct(PushPermissionService $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     *  Get the extension name
     *
     *  @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     *  Get an array of Twig Functions
     *
     *  @return array
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('canUserPush', array($this, 'canUserPush'))
        );
    }

    /**
     *  Check if a user can push to a repo to a given env
     *
     *  @param string $user
     *  @param string $repo
     *  @param string $env
     *  @return bool
     */
    public function canUserPush($user, $repo, $env)
    {
        return $this->permissions->canUserPushToEnvRepo($user, $repo, $env);
    }
}
