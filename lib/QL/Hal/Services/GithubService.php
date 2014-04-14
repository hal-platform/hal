<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use Github\Api\GitData\References;
use Github\Api\PullRequest;
use Github\Api\Repo;
use Github\Exception\RuntimeException;
use Github\ResultPager;
use QL\Hal\GithubApi\HackUser;

/**
 * You know whats really annoying? Wrapping every api request in a try/catch.
 *
 * This helps abstract the awfulness of the github api library from the rest of Hal.
 */
class GithubService
{
    /**
     * @var HackUser
     */
    private $userApi;

    /**
     * @var Repo
     */
    public $repoApi;

    /**
     * @var References
     */
    private $refApi;

    /**
     * @var PullRequest
     */
    private $pullApi;

    /**
     * @var ResultPager
     */
    private $pager;

    /**
     * @param HackUser $user
     * @param Repo $repo
     * @param References $ref
     * @param PullRequest $pullApi
     * @param ResultPager $pager
     */
    public function __construct(HackUser $user, Repo $repo, References $ref, PullRequest $pull, ResultPager $pager)
    {
        $this->userApi = $user;
        $this->repoApi = $repo;
        $this->refApi = $ref;
        $this->pullApi = $pull;
        $this->pager = $pager;
    }

    /**
     * Get the reference data for branches for a repository.
     *
     * @param string $user
     * @param string $repo
     * @return array
     */
    public function branches($user, $repo)
    {
        try {
            $refs = $this->pager->fetchAll($this->refApi, 'branches', [$user, $repo]);
        } catch (RuntimeException $e) {
            $refs = [];
        }

        array_walk($refs, function(&$data) {
            $data['name'] = str_replace('refs/heads/', '', $data['ref']);
        });

        return $refs;
    }

    /**
     * Get most recent closed pull requests for a repository.
     *
     * This only gets the most recent 30. If you need more than that, get all of them.
     *
     * It does not appear possible to get both open and closed pull requests from the same api call,
     * even though the api documentation specifies it is.
     *
     * @param string $user
     * @param string $repo
     * @param boolean $getAll
     * @return array
     */
    public function closedPullRequests($user, $repo, $getAll = false)
    {
        try {
            if ($getAll) {
                $pulls = $this->pager->fetchAll($this->pullApi, 'all', [$user, $repo, 'closed']);
            } else {
                $pulls = $this->pullApi->all($user, $repo, 'closed');
            }
        } catch (RuntimeException $e) {
            $pulls = [];
        }

        return $pulls;
    }

    /**
     * Get all open pull requests for a repository.
     *
     * It does not appear possible to get both open and closed pull requests from the same api call,
     * even though the api documentation specifies it is.
     *
     * @param string $user
     * @param string $repo
     * @return array
     */
    public function openPullRequests($user, $repo)
    {
        try {
            $pulls = $this->pager->fetchAll($this->pullApi, 'all', [$user, $repo]);
        } catch (RuntimeException $e) {
            $pulls = [];
        }

        return $pulls;
    }

    /**
     * Get the extended metadata for a repository.
     *
     * @param string $user
     * @param string $repo
     * @return array|null
     */
    public function repository($user, $repo)
    {
        try {
            $repository = $this->repoApi->show($user, $repo);
        } catch (RuntimeException $e) {
            $repository = null;
        }

        return $repository;
    }

    /**
     * Get all repositories for a user.
     *
     * @param string $user
     * @return array
     */
    public function repositories($user)
    {
        try {
            $repositories = $this->pager->fetchAll($this->userApi, 'repositories', [$user]);
        } catch (RuntimeException $e) {
            $repositories = [];
        }

        return $repositories;
    }

    /**
     * Get the reference data for tags for a repository.
     *
     * @param string $user
     * @param string $repo
     * @return array
     */
    public function tags($user, $repo)
    {
        try {
            $refs = $this->pager->fetchAll($this->refApi, 'tags', [$user, $repo]);
        } catch (RuntimeException $e) {
            $refs = [];
        }

        array_walk($refs, function(&$data) {
            $data['name'] = str_replace('refs/tags/', '', $data['ref']);
        });

        return $refs;
    }

    /**
     * Get the extended metadata for a user.
     *
     * @param string $user
     * @return array|null
     */
    public function user($user)
    {
        try {
            $user = $this->userApi->show($user);
        } catch (RuntimeException $e) {
            $user = null;
        }

        return $user;
    }

    /**
     * Get the extended metadata for all github users.
     *
     * @return array
     */
    public function users()
    {
        try {
            $users = $this->pager->fetchAll($this->userApi, 'all');
        } catch (RuntimeException $e) {
            $users = [];
        }

        return $users;
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
}
