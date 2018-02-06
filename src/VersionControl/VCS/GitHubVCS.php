<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl\VCS;

use Github\HttpClient\Builder;
use Github\ResultPager;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\VersionControl\GitHub\MCPCachePlugin;
use Hal\Core\VersionControl\VCS\GitHubVCS as BaseGitHubVCS;
use Hal\UI\Service\GitHubService;
use Hal\UI\VersionControl\GitHub\GitHubResolver;
use Hal\UI\VersionControl\GitHub\GitHubURLBuilder;

// @todo - this needs to be merged and combined into the hal-core version
class GitHubVCS extends BaseGitHubVCS
{
    /**
     * @var string
     */
    private $baseURL;

    /**
     * @param MCPCachePlugin $cachePlugin
     * @param Builder $httpClientBuilder
     * @param string $githubBaseURL
     */
    public function __construct(MCPCachePlugin $cachePlugin, Builder $httpClientBuilder, string $githubBaseURL)
    {
        parent::__construct($cachePlugin, $httpClientBuilder, $githubBaseURL);

        $this->baseURL = rtrim($githubBaseURL, '/');
    }

    /**
     * @param VersionControlProvider $vcs
     *
     * @return GitHubService|null
     */
    public function buildService(VersionControlProvider $vcs): ?GitHubService
    {
        $client = $this->buildClient($vcs);
        if (!$client) {
            return null;
        }

        $resolver = new GitHubResolver($client);
        $pager = new ResultPager($client);
        $builder = new GitHubURLBuilder($resolver, $this->baseURL);

        return new GitHubService($client, $resolver, $builder, $pager);
    }
}
