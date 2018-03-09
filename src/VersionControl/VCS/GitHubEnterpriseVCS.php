<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl\VCS;

use Github\ResultPager;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\VersionControl\VCS\GitHubEnterpriseVCS as BaseGitHubEnterpriseVCS;
use Hal\UI\Parameters;
use Hal\UI\Service\GitHubService;
use Hal\UI\VersionControl\GitHub\GitHubResolver;
use Hal\UI\VersionControl\GitHub\GitHubURLBuilder;

// @todo - this needs to be merged and combined into the hal-core version
class GitHubEnterpriseVCS extends BaseGitHubEnterpriseVCS
{
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

        $baseURL = $vcs->parameter(Parameters::VCS_GHE_URL);

        $resolver = new GitHubResolver($client);
        $pager = new ResultPager($client);
        $builder = new GitHubURLBuilder($resolver, $baseURL);

        return new GitHubService($client, $resolver, $builder, $pager);
    }
}
