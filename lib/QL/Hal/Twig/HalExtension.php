<?php
# lib/QL/Hal/Twig/HalExtension.php

namespace QL\Hal\Twig;

use DateTime;
use DateTimeZone;
use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
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
            new Twig_SimpleFunction('canUserPush', array($this, 'canUserPush')),
            new Twig_SimpleFunction('isUserAdmin', array($this, 'isUserAdmin'))
        );
    }

    /**
     *  Get an array of Twig Filters
     *
     *  @return array
     */
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('dateHal', array($this, 'datetimeConvertAndFormat'))
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

    /**
     *  Check if a user is an admin
     *
     *  @param $user
     *  @return bool
     */
    public function isUserAdmin($user)
    {
        return $this->permissions->isUserAdmin($user);
    }

    /**
     *  Convert a UTC encoded MySQL DateTime string to a DateTime object... or just use the passed DateTime object
     *  if it is one... because fuck PDO
     *
     *  @param string $value
     *  @param string $format
     *  @param string $timezone
     *  @return false|DateTime
     */
    public function datetimeConvertAndFormat($value, $format = 'M j, Y g:i A', $timezone = 'America/Detroit')
    {
        if ($value instanceof DateTime) {
            $datetime = $value;
        } else {
            $datetime = DateTime::createFromFormat('Y-m-d G:i:s', $value, new DateTimeZone('UTC'));

            if ($datetime === false) {
                $datetime = new DateTime($value, new DateTimeZone('UTC'));
            }
        }

        if ($datetime instanceof DateTime) {
            return $datetime->setTimezone(new DateTimeZone($timezone))->format($format);
        } else {
            return '';
        }
    }
}
