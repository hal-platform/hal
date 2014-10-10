<?php
# lib/QL/Hal/Twig/HalExtension.php

namespace QL\Hal\Twig;

use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
use Twig_SimpleTest;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\User as DomainUser;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Services\GithubService;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Core\Entity;

/**
 *  Twig Extension for HAL9000
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    private $permissions;

    private $url;

    private $github;

    private $time;

    /**
     * Constructor
     *
     * @param PermissionsService $permissions
     * @param UrlHelper $url
     * @param GithubService $github
     * @param TimeHelper $time
     */
    public function __construct(
        PermissionsService $permissions,
        UrlHelper $url,
        GithubService $github,
        TimeHelper $time
    ) {
        $this->permissions = $permissions;
        $this->url = $url;
        $this->github = $github;
        $this->time = $time;
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
            new Twig_SimpleFunction('canUserBuild', array($this->permissions, 'allowBuild')),
            new Twig_SimpleFunction('canUserDelete', array($this->permissions, 'allowDelete')),
            new Twig_SimpleFunction('isUserAdmin', array($this->permissions, 'allowAdmin')),
            new Twig_SimpleFunction('showAnalytics', array($this->permissions, 'showAnalytics')),
            new Twig_SimpleFunction('urlFor', array($this->url, 'urlFor')),
            new Twig_SimpleFunction('uriFor', array($this->url, 'uriFor')),
            new Twig_SimpleFunction('githubRepo', array($this->url, 'githubRepoUrl')),
            new Twig_SimpleFunction('githubCommit', array($this->url, 'githubCommitUrl')),
            new Twig_SimpleFunction('githubTreeish', array($this->url, 'githubTreeUrl')),
            new Twig_SimpleFunction('githubPullRequest', array($this->url, 'githubPullRequestUrl')),
            new Twig_SimpleFunction('githubReference', array($this->url, 'githubReferenceUrl')),
            new Twig_SimpleFunction('githubRelease', array($this->url, 'githubReleaseUrl')),
            new Twig_SimpleFunction('getUsersActualName', array($this, 'getUsersActualName')),
            new Twig_SimpleFunction('githubCommitIsCurrent', array($this, 'commitIsCurrent'))
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
            new Twig_SimpleFilter('dateHal', array($this->time, 'format'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('date', array($this->time, 'format'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('reldate', array($this->time, 'relative'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('chunk', array($this, 'arrayChunk')),
            new Twig_SimpleFilter('jsonPretty', array($this, 'jsonPretty')),
            new Twig_SimpleFilter('gitref', array($this->url, 'formatGitReference')),
            new Twig_SimpleFilter('commit', array($this->url, 'formatGitCommit')),
            new Twig_SimpleFilter('formatBuildId', array($this, 'formatBuildId')),
            new Twig_SimpleFilter('formatPushId', array($this, 'formatPushId'))
        );
    }

    /**
     * Get an array of Twig Tests
     *
     * @return array
     */
    public function getTests()
    {
        return [
            new Twig_SimpleTest('build', function ($entity) { return $entity instanceof Entity\Build; }),
            new Twig_SimpleTest('push', function ($entity) { return $entity instanceof Entity\Push; })
        ];
    }

    /**
     * Check if a commit hash is the most recent for a given Github user, repo, and reference
     *
     * @param $user
     * @param $repo
     * @param $reference
     * @param $commit
     * @return bool
     */
    public function commitIsCurrent($user, $repo, $reference, $commit)
    {
        $resolve = $this->github->resolve($user, $repo, $reference);
        $current = (is_array($resolve)) ? $resolve[1] : null;

        return ($current == $commit) ? true : false;
    }

    /**
     * Chunk an array into $split roughly equal parts
     *
     * @param array $input
     * @param int $split
     * @return array
     */
    public function arrayChunk(array $input, $split)
    {
        $count = ceil((count($input) / (int)$split));
        $chunks = array_chunk($input, $count);

        return array_pad($chunks, $split, []);
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

    /**
     * Attempt to pretty print JSON string
     *
     * @param $json
     * @return string
     */
    public function jsonPretty($json)
    {
        $raw = json_decode($json, true);

        // bail on badly formatted json
        if ($raw === null) {
            return $json;
        }

        return json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string|int $id
     * @return string
     */
    public function formatBuildId($id)
    {
        if (substr($id, 0, 1) == 'b') {
            return strtolower(substr($id, 6));
        }

        return substr($id, 0, 10);
    }

    /**
     * @param string|int $id
     * @return string
     */
    public function formatPushId($id)
    {
        if (substr($id, 0, 1) == 'p') {
            return strtolower(substr($id, 6));
        }

        return $id;
    }
}
