<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl\GitHub;

class GitHubURLBuilder
{
    /**
     * @var GitHubResolver
     */
    private $resolver;

    /**
     * @var string
     */
    private $githubBaseURL;

    /**
     * @param GitHubResolver $resolver
     * @param string $githubBaseURL
     */
    public function __construct(GitHubResolver $resolver, $githubBaseURL)
    {
        $this->resolver = $resolver;
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
     * @param string $type
     * @param string $reference
     *
     * @return string
     */
    public function githubRefURL($user, $repo, $type, $reference)
    {
        switch ($type) {
            case 'commit':
                return $this->githubCommitURL($user, $repo, $reference);

            case 'tag':
                return $this->githubReleaseURL($user, $repo, $reference);

            case 'pull':
                return $this->githubPullRequestURL($user, $repo, $reference);

            case 'branch':
            default:
                return $this->githubBranchURL($user, $repo, $reference);
        }
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
        if ($tag = $this->resolver->parseRefAsTag($reference)) {
            return $this->githubReleaseURL($user, $repo, $tag);
        }

        if ($pull = $this->resolver->parseRefAsPull($reference)) {
            return $this->githubPullRequestURL($user, $repo, $pull);
        }

        if ($commit = $this->resolver->parseRefAsCommit($reference)) {
            return $this->githubCommitURL($user, $repo, $commit);
        }

        // default to branch
        return $this->githubBranchURL($user, $repo, $reference);
    }
}
