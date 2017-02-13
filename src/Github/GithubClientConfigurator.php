<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Github;

use Github\Client;
use Github\HttpClient\Builder;

class GithubClientConfigurator
{
    /**
     * @var MCPCachePlugin
     */
    private $cachePlugin;
    /**
     * @var Builder
     */
    private $httpClientBuilder;

    public function __construct(MCPCachePlugin $cachePlugin, Builder $httpClientBuilder)
    {
        $this->cachePlugin = $cachePlugin;
        $this->httpClientBuilder = $httpClientBuilder;
    }

    public function configure(Client $client)
    {
        //super hacky and dumb but knplabs sucks!
        //The builder MUST BE the same instance that was passed in the Github\Client::__construct();
        //or else this wont do anything.

        //Since the github client's cache only supports PSR6 and we don't have a PSR6 cache we need
        //to make sure that the cache plugin we've written is placed at the end of the plugins to run.
        $this->httpClientBuilder->addPlugin($this->cachePlugin);

        return $client;
    }
}
