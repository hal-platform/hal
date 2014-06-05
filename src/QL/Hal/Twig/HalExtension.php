<?php
# lib/QL/Hal/Twig/HalExtension.php

namespace QL\Hal\Twig;

use DateTime;
use DateTimeZone;
use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
use MCP\Corp\Account\User as LdapUser;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\User as DomainUser;
use QL\Hal\Helpers\UrlHelper;
use Slim\Slim;
use QL\Hal\Services\PermissionsService;


/**
 *  Twig Extension for HAL9000
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    private $permissions;

    private $slim;

    private $url;

    /**
     *  Constructor
     *
     *  @param PermissionsService $permissions
     *  @param UrlHelper $url
     */
    public function __construct(PermissionsService $permissions, UrlHelper $url)
    {
        $this->permissions = $permissions;
        $this->url = $url;
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
            new Twig_SimpleFunction('canUserPush', array($this->permissions, 'allowPush')),
            new Twig_SimpleFunction('canUserDelete', array($this->permissions, 'allowDelete')),
            new Twig_SimpleFunction('isUserAdmin', array($this->permissions, 'allowAdmin')),
            new Twig_SimpleFunction('urlFor', array($this->url, 'urlFor')),
            new Twig_SimpleFunction('githubRepo', array($this->url, 'githubRepoUrl')),
            new Twig_SimpleFunction('githubCommit', array($this->url, 'githubCommitUrl')),
            new Twig_SimpleFunction('githubTreeish', array($this->url, 'githubTreeUrl')),
            new Twig_SimpleFunction('githubPullRequest', array($this->url, 'githubPullRequestUrl')),
            new Twig_SimpleFunction('getUsersActualName', array($this, 'getUsersActualName'))
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
            new Twig_SimpleFilter('dateHal', array($this, 'datetimeConvertAndFormat')),
            new Twig_SimpleFilter('date', array($this, 'datetimeConvertAndFormat'))
        );
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
        if ($value instanceof TimePoint) {
            return $value->format($format, $timezone);
        }

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

    /**
     *  Get the users actual name
     *
     *  @param $user
     *  @return string
     */
    public function getUsersActualName($user)
    {
        $name = '';
        if ($user instanceof LdapUser) {
            $name = $user->firstName();

        } elseif ($user instanceof DomainUser) {
            $name = $user->getName();
        }

        if (preg_match('/(Dave|David)/', $name) === 1) {
            return 'Frank';
        }

        return 'Dave';
    }
}
