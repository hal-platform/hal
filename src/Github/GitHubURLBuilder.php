<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Github;

use QL\Hal\Service\GitHubService;

class GitHubURLBuilder
{
    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @type string
     */
    private $githubBaseURL;

    /**
     * @param GitHubService $github
     * @param string $githubBaseURL
     */
    public function __construct(GitHubService $github, $githubBaseURL)
    {
        $this->github = $github;
        $this->githubBaseURL = rtrim($githubBaseURL, '/');
    }

    /**
     * @param string $user
     *
     * @return string
     */
    public function githubUserURL($user)
    {
        return sprintf('%s/%s', $this->githubBaseURL, $user);
    }

    /**
     * @param string $user
     * @param string $repo
     * @param string $suffix
     *
     * @return string
     */
    public function githubRepoURL($user, $repo, $suffix = '')
    {
        $url = sprintf('%s/%s', $this->githubUserURL($user), $repo);

        if ($suffix) {
            $url .= $suffix;
        }

        return $url;
    }

    /**
     * @param string $user
     * @param string $repo
     * @param string $commit
     *
     * @return string
     */
    public function githubCommitURL($user, $repo, $commit)
    {
        return $this->githubRepoURL($user, $repo, sprintf('/commit/%s', $commit));
    }

    /**
     * @param string $user
     * @param string $repo
     * @param string $branch
     *
     * @return string
     */
    public function githubBranchURL($user, $repo, $branch)
    {
        return $this->githubRepoURL($user, $repo, sprintf('/tree/%s', $branch));
    }

    /**
     * @param string $user
     * @param string $repo
     * @param string $tag
     *
     * @return string
     */
    public function githubReleaseURL($user, $repo, $tag)
    {
        return $this->githubRepoURL($user, $repo, sprintf('/releases/tag/%s', $tag));
    }

    /**
     * @param string $user
     * @param string $repo
     * @param string $number
     *
     * @return string
     */
    public function githubPullRequestURL($user, $repo, $number)
    {
        return $this->githubRepoURL($user, $repo, sprintf('/pull/%s', $number));
    }

    /**
     * Get the URL for an arbitrary Github reference
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     *
     * @return string
     */
    public function githubReferenceURL($user, $repo, $reference)
    {
        if ($tag = $this->github->parseRefAsTag($reference)) {
            return $this->githubReleaseURL($user, $repo, $tag);
        }

        if ($pull = $this->github->parseRefAsPull($reference)) {
            return $this->githubPullRequestURL($user, $repo, $pull);
        }

        if ($commit = $this->github->parseRefAsCommit($reference)) {
            return $this->githubCommitURL($user, $repo, $commit);
        }

        // default to branch
        return $this->githubBranchURL($user, $repo, $reference);
    }
}
