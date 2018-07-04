<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl;

use Hal\Core\Entity\Application;
use Hal\Core\Parameters;
use Hal\Core\VersionControl\VCSFactory;
use Hal\Core\VersionControl\VCSClientInterface;

class BuildableRefs
{
    /**
     * @var VCSFactory
     */
    private $vcs;

    /**
     * @param VCSFactory $vcs
     */
    public function __construct(VCSFactory $vcs)
    {
        $this->vcs = $vcs;
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    public function getVCSData(Application $application)
    {
        $client = $this->getVCSClient($application);
        if (!$client) {
            return [
                'gh_branches' => [],
                'gh_tags' => [],
                'gh_pr_open' => [],
                'gh_pr_closed' => [],
            ];
        }

        ['client' => $client, 'params' => $params] = $client;

        $branches = $client->branches(...$params);
        $tags = array_slice($client->tags(...$params), 0, 25);

        $open = $client->pullrequests(...array_merge($params, [['state' => 'open']]));
        $closed = $client->pullrequests(...array_merge($params, [['state' => 'closed']]));

        return [
            'gh_branches' => $branches,
            'gh_tags' => $tags,
            'gh_pr_open' => $open,
            'gh_pr_closed' => $closed,
        ];
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    private function getVCSClient(Application $application)
    {
        $provider = $application->provider();
        if (!$provider) {
            return [];
        }

        $client = $this->vcs->authenticate($provider);
        if (!$client) {
            return [];
        }

        return [
            'client' => $client,
            'params' => [
                $application->parameter(Parameters::VC_GH_OWNER),
                $application->parameter(Parameters::VC_GH_REPO),
            ],
        ];
    }
}
