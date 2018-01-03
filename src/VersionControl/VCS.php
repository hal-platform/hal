<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl;

use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Service\GitHubService;

class VCS
{
    use ValidatorErrorTrait;

    const ERR_VCS_MISCONFIGURED = 'No valid Version Control Provider was found. Hal may be misconfigured.';

    /**
     * @var array
     */
    private $adapters;

    /**
     * @param array $adapters
     */
    public function __construct(array $adapters = [])
    {
        $this->adapters = [];

        foreach ($adapters as $type => $adapter) {
            $this->addAdapter($type, $adapter);
        }
    }

    /**
     * @param string $type
     * @param ???? $adapter
     *
     * @return void
     */
    public function addAdapter($type, $adapter): void
    {
        $this->adapters[$type] = $adapter;
    }

    /**
     * The typehint of this needs to change to be less github specific.
     *
     * @param VersionControlProvider $vcs
     *
     * @return GitHubService|null
     */
    public function authenticate(VersionControlProvider $vcs): ?GitHubService
    {
        $adapter = $this->adapters[$vcs->type()] ?? null;
        if (!$adapter) {
            $this->addError(self::ERR_VCS_MISCONFIGURED);
            return null;
        }

        if ($vcs->type() === VCSProviderEnum::TYPE_GITHUB) {
            $vcsService = $adapter->buildService($vcs);

        } elseif ($vcs->type() === VCSProviderEnum::TYPE_GITHUB_ENTERPRISE) {
            $vcsService = $adapter->buildService($vcs);

        } else {
            $this->addError(self::ERR_VCS_MISCONFIGURED);
            return null;
        }

        if ($vcsService instanceof GitHubService) {
            return $vcsService;
        }

        return $adapter->errors();
    }
}
