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
use QL\Hal\PushPermissionService;
use Slim\Slim;


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
     *  @param PushPermissionService $permissions
     *  @param UrlHelper $url
     */
    public function __construct(PushPermissionService $permissions, UrlHelper $url)
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
            new Twig_SimpleFunction('canUserPush', array($this, 'canUserPush')),
            new Twig_SimpleFunction('isUserAdmin', array($this, 'isUserAdmin')),
            new Twig_SimpleFunction('isUserKeymaster', array($this, 'isUserKeymaster')),
            new Twig_SimpleFunction('urlFor', array($this, 'urlFor')),
            new Twig_SimpleFunction('githubRepo', array($this, 'githubRepo')),
            new Twig_SimpleFunction('githubCommit', array($this, 'githubCommit')),
            new Twig_SimpleFunction('githubTreeish', array($this, 'githubTreeish')),
            new Twig_SimpleFunction('githubPullRequest', array($this, 'githubPullRequest')),
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
     *  Check if a user is a keymaster
     *
     *  @param $user
     *  @return bool
     */
    public function isUserKeymaster($user)
    {
        return $this->permissions->isUserKeymaster($user);
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
     *  Generate a URL by route name and parameters
     *
     *  @param string $route
     *  @param array $params
     *  @return string
     */
    public function urlFor($route, array $params = [])
    {
        return $this->url->urlFor($route, $params);
    }

    /**
     *  Get the url for a Github repository
     *
     *  @param string $user
     *  @param string $repo
     *  @return string
     */
    public function githubRepo($user, $repo)
    {
        return $this->url->githubRepoUrl($user, $repo);
    }

    /**
     *  Get the url for a Github repository commit
     *
     *  @param $user
     *  @param $repo
     *  @param $commit
     *  @return mixed
     */
    public function githubCommit($user, $repo, $commit)
    {
        return $this->url->githubCommitUrl($user, $repo, $commit);
    }

    /**
     *  Get the url for a Github repository treeish
     *
     *  @param $user
     *  @param $repo
     *  @param $treeish
     *  @return string
     */
    public function githubTreeish($user, $repo, $treeish)
    {
        return $this->url->githubTreeUrl($user, $repo, $treeish);
    }

    /**
     *  Get the url for a Github pull request
     *
     *  @param $user
     *  @param $repo
     *  @param $number
     *  @return string
     */
    public function githubPullRequest($user, $repo, $number)
    {
        return $this->url->githubPullRequestUrl($user, $repo, $number);
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
