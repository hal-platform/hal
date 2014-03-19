<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use DateTime;
use DateTimeZone;
use Exception;
use MCP\Corp\Account\User;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\LogService;
use QL\Hal\Services\SyncOptions;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Session;

/**
 * @api
 */
class SyncHandler
{
    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var SyncOptions
     */
    private $syncOptions;

    /**
     * @var LogService
     */
    private $logService;

    /**
     * @var DeploymentService
     */
    private $depService;

    /**
     * @var User
     */
    private $currentUserContext;

    /**
     * @var string
     */
    private $pusherScriptLocation;

    /**
     * @var string
     */
    private $buildUser;

    /**
     * @var string
     */
    private $sshUser;

    /**
     * @var bool
     */
    private $debugMode;

    /**
     * @param Twig_Template $tpl
     * @param SyncOptions $syncOptions
     * @param LogService $logService
     * @param DeploymentService $depService
     * @param Session $session
     * @param string $pusherScriptLocation
     * @param string $buildUser
     * @param string $sshUser
     * @param boolean $debugMode
     */
    public function __construct(
        Twig_Template $tpl,
        SyncOptions $syncOptions,
        LogService $logService,
        DeploymentService $depService,
        Session $session,
        $pusherScriptLocation,
        $buildUser,
        $sshUser,
        $debugMode
    ) {
        $this->tpl = $tpl;
        $this->syncOptions = $syncOptions;
        $this->logService = $logService;
        $this->depService = $depService;
        $this->currentUserContext = $session->get('account');
        $this->pusherScriptLocation = $pusherScriptLocation;
        $this->buildUser = $buildUser;
        $this->sshUser = $sshUser;
        $this->debugMode = $debugMode;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array|null $params
     * @param callable|null $notFound
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $repoShortName = $params['name'];
        $deps = $req->get('deps');

        if (!is_array($deps)) {
            $deps = [];
        }

        $options = $this->syncOptions->syncOptionsByRepoShortName($repoShortName, $deps);

        if (!isset($options['repo'])) {
            call_user_func($notFound);
            return;
        }

        if (!$options['deps']) {
            $res->setBody($this->tpl->render($options));
            return;
        }

        $commitish = $req->post('commitish');
        $sha = $req->post('sha');

        list($branch, $commit) = $this->derefCommitish($commitish, $sha, $options);

        if ($commit === null) {
            $options['toolong'] = false;
            if (count($options['branches']) > 100) {
                $options['toolong'] = 100;
            }

            $res->setBody($this->tpl->render($options));
            return;
        }

        foreach ($options['deps'] as $dep) {
            $this->syncDeployment($dep, $options, $branch, $commit, $this->debugMode);
        }

        $res->status(303);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/r/' . $options['repo']['ShortName']);
    }

    /**
     * @param string $name
     * @param array $branches
     * @return string|null
     */
    private function shaForBranchTag($name, $branches)
    {
        foreach ($branches as $branch) {
            if ($branch['name'] == $name) {
                return $branch['commit']['sha'];
            }
        }
        return null;
    }

    private function syncCmdCreate($sudoUser, $pusherLocation, $depId, $logId, $isDebug, array $envVars)
    {
        $cmdStart = 'sudo -n -H -u %s';
        $cmdEnvs = ' ';
        $cmdCmd = '%s %s %s';
        //if ($isDebug) {
        //    $cmdCmd .= ' 2>&1';
        //} else {
            $cmdCmd .= ' &>/dev/null';
        //}
        foreach ($envVars as $k => $v) {
            $cmdEnvs .= escapeshellarg($k) . '=' . escapeshellarg($v) . ' ';
        }

        $cmd = sprintf($cmdStart, escapeshellarg($sudoUser));
        $cmd .= $cmdEnvs;
        $cmd .= sprintf($cmdCmd, escapeshellarg($pusherLocation), escapeshellarg($depId), escapeshellarg($logId));

        return $cmd;
    }

    /**
     * @param $commitish
     * @param $sha
     * @param $options
     * @return array
     */
    private function derefCommitish($commitish, $sha, $options)
    {
        if ($commitish === '(no branch)') {
            $branch = '(no branch)';
            $commit = $sha;

            return array($branch, $commit);
        } else if ($commit = $this->shaForBranchTag($commitish, $options['branches'])) {
            $branch = $commitish;

            return array($branch, $commit);
        } else if ($commit = $this->shaForBranchTag($commitish, $options['tags'])) {
            $branch = $commitish;

            return array($branch, $commit);
        } else {
            $branch = null;
            $commit = null;

            return array($branch, $commit);
        }
    }

    /**
     * @param $dep
     * @param $options
     * @param $branch
     * @param $commit
     * @param $isDebug
     * @throws \Exception
     */
    private function syncDeployment($dep, $options, $branch, $commit, $isDebug)
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->depService->update($dep['DeploymentId'], DeploymentService::STATUS_DEPLOYING, $dep['CurBranch'], $dep['CurCommit'], $dep['LastPushed']);
        $logid = $this->logService->create(
            $now,
            $this->currentUserContext->commonId(),
            $this->currentUserContext->displayName(),
            $options['repo']['ShortName'],
            $branch,
            $commit,
            $dep['Environment'],
            $dep['HostName'],
            $dep['TargetPath']
        );

        $cmd = $this->syncCmdCreate(
            $this->buildUser,
            $this->pusherScriptLocation,
            $dep['DeploymentId'],
            $logid,
            $isDebug,
            [
                'PATH' => '/usr/local/zend/bin:/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin',
                'HAL_COMMIT' => $commit,
                'HAL_GITREF' => $branch,
                'HAL_ENVIRONMENT' => $dep['Environment'],
                'HAL_HOSTNAME' => $dep['HostName'],
                'HAL_PATH' => $dep['TargetPath'],
                'HAL_USER' => $this->currentUserContext->windowsUsername(),
                'HAL_USER_DISPLAY' => $this->currentUserContext->displayName(),
                'HAL_COMMONID' => $this->currentUserContext->commonId(),
                'HAL_REPO' => $options['repo']['ShortName'],
            ]
        );

        exec($cmd, $out, $ret);

        if (0 !== $ret) {
            throw new Exception('Tried (and failed) to run `' . $cmd . '`' . "\n\n" . implode("\n", $out));
        }
    }
}
