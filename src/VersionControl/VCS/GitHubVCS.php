<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl\VCS;

use Github\Client;
use Github\HttpClient\Builder;
use Github\ResultPager;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Service\GitHubService;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\VersionControl\GitHub\GitHubResolver;
use Hal\UI\VersionControl\GitHub\GitHubURLBuilder;
use Hal\UI\VersionControl\GitHub\MCPCachePlugin;
use QL\MCP\Cache\CachingTrait;

class GitHubVCS
{
    use CachingTrait;
    use ValidatorErrorTrait;

    const DEFAULT_GITHUB_URL = 'https://github.com';

    // todo move to VCSProviderEnum?
    const PARAM_TOKEN = 'gh.token';

    const ERR_VCS_MISCONFIGURED = 'GitHub.com Version Control Provider is misconfigured.';

    /**
     * @var MCPCachePlugin
     */
    private $cachePlugin;

    /**
     * @var Builder
     */
    private $httpClientBuilder;

    /**
     * @var string
     */
    private $baseURL;

    /**
     * @var bool
     */
    private $isCachedAdded;

    /**
     * @param MCPCachePlugin $cachePlugin
     * @param Builder $httpClientBuilder
     * @param string $githubBaseURL
     */
    public function __construct(MCPCachePlugin $cachePlugin, Builder $httpClientBuilder, string $githubBaseURL)
    {
        $this->cachePlugin = $cachePlugin;
        $this->httpClientBuilder = $httpClientBuilder;

        $this->baseURL = rtrim($githubBaseURL, '/');

        $this->isCachedAdded = false;
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

    /**
     * @param VersionControlProvider $vcs
     *
     * @return Client|null
     */
    private function buildClient(VersionControlProvider $vcs): ?Client
    {
        if ($vcs->type() !== VCSProviderEnum::TYPE_GITHUB) {
            $this->addError(self::ERR_VCS_MISCONFIGURED);
            return null;
        }

        $key = sprintf('vcs_clients:%s:%s', $vcs->type(), $vcs->id());

        $client = $this->getFromCache($key);
        if ($client instanceof Client) {
            return $client;
        }

        $token = $vcs->parameter(self::PARAM_TOKEN);
        if (!$token) {
            $this->addError(self::ERR_VCS_MISCONFIGURED);
            return null;
        }

        $client = new Client($this->httpClientBuilder, null);
        $client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);

        if (!$this->isCachedAdded) {
            //Since the github client's cache only supports PSR6 and we don't have a PSR6 cache we need
            //to make sure that the cache plugin we've written is placed at the end of the plugins to run.
            $this->httpClientBuilder->addPlugin($this->cachePlugin);
            $this->isCachedAdded = true;
        }

        // Should only be in memory
        $this->setToCache($key, $client, 60 * 60);

        return $client;
    }
}
