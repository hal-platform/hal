<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\GithubApi;

use Github\Api\Repo;
use Github\Exception\RuntimeException;
use Github\ResultPager;

/**
 * You know whats really annoying? Wrapping every api request in a try/catch.
 *
 * This helps abstract the awfulness of the github api library from the rest of Hal.
 */
class GithubApi
{
    /**
     * @var HackUser
     */
    private $userApi;

    /**
     * @var Repo
     */
    private $repoApi;

    /**
     * @var ResultPager
     */
    private $pager;

    /**
     * @param HackUser $user
     * @param Repo $repo
     * @param ResultPager $pager
     */
    public function __construct(HackUser $user, Repo $repo, ResultPager $pager)
    {
        $this->userApi = $user;
        $this->repoApi = $repo;
        $this->pager = $pager;
    }

    /**
     * @param string $owner
     * @param string $repo
     * @param string $user
     * @return boolean
     */
    public function isUserCollaborator($owner, $repo, $user)
    {
        try {
            // A successful response returns 'null'
            $this->repoApi->collaborators()->check($owner, $repo, $user);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $user
     * @return array
     */
    public function getRepositoriesByUser($user)
    {
         try {
            $repositories = $this->pager->fetchAll($this->userApi, 'repositories', [$user]);
        } catch (RuntimeException $e) {
            $repositories = [];
        }

        return $repositories;
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        try {
            $users = $this->pager->fetchAll($this->userApi, 'all');
        } catch (RuntimeException $e) {
            $users = [];
        }

        return $users;
    }
}
