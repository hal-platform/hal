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
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\LogService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class SyncHandler
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

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
     * @var array
     */
    private $session;

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
     * @param Request $request
     * @param Response $response
     * @param Twig_Template $tpl
     * @param SyncOptions $syncOptions
     * @param LogService $logService
     * @param DeploymentService $depService
     * @param array $session
     * @param string $pusherScriptLocation
     * @param string $buildUser
     * @param string $sshUser
     */
    public function __construct(
        Request $request,
        Response $response,
        Twig_Template $tpl,
        SyncOptions $syncOptions,
        LogService $logService,
        DeploymentService $depService,
        array $session,
        $pusherScriptLocation,
        $buildUser,
        $sshUser
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->tpl = $tpl;
        $this->syncOptions = $syncOptions;
        $this->logService = $logService;
        $this->depService = $depService;
        $this->session = $session;
        $this->pusherScriptLocation = $pusherScriptLocation;
        $this->buildUser = $buildUser;
        $this->sshUser = $sshUser;
    }

    /**
     * @param $repoShortName
     * @param callable $notFound
     * @throws Exception
     */
    public function __invoke($repoShortName, callable $notFound)
    {
        $deps = $this->request->get('deps');

        if (!is_array($deps)) {
            $deps = [];
        }

        $options = $this->syncOptions->syncOptionsByRepoShortName($repoShortName, $deps);

        if (!isset($options['repo'])) {
            call_user_func($notFound);
            return;
        }

        if (!$options['deps']) {
            $this->response->setBody($this->tpl->render($options));
            return;
        }

        $commitish = $this->request->post('commitish');
        $sha = $this->request->post('sha');

        if ($commitish === '(no branch)') {
            $branch = '(no branch)';
            $commit = $sha;
        } else if ($commit = $this->shaForBranchTag($commitish, $options['branches'])) {
            $branch = $commitish;
        } else if ($commit = $this->shaForBranchTag($commitish, $options['tags'])) {
            $branch = $commitish;
        } else {
            $branch = null;
            $commit = null;
        }

        if ($commit === null) {
            $options['toolong'] = false;
            if (count($options['branches']) > 100) {
                $options['toolong'] = 100;
            }

            $this->response->setBody($this->tpl->render($options));
            return;
        }

        foreach ($options['deps'] as $dep) {
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $this->depService->update($dep['DeploymentId'], DeploymentService::STATUS_DEPLOYING, $dep['CurBranch'], $dep['CurCommit'], $dep['LastPushed']);
            $logid = $this->logService->create(
                $now,
                $this->session['commonid'],
                $this->session['account']['displayname'],
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
                [
                    'PATH' => '/usr/local/zend/bin:/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin',
                    'HAL_COMMIT' => $commit,
                    'HAL_GITREF' => $branch,
                    'HAL_ENVIRONMENT' => $dep['Environment'],
                    'HAL_HOSTNAME' => $dep['HostName'],
                    'HAL_PATH' => $dep['TargetPath'],
                    'HAL_USER' => $this->session['account']['samaccountname'],
                    'HAL_USER_DISPLAY' => $this->session['account']['displayname'],
                    'HAL_COMMONID' => $this->session['commonid'],
                    'HAL_REPO' => $options['repo']['ShortName'],

                ]
            );

            exec($cmd, $out, $ret);

            if (0 !== $ret) {
                throw new Exception('Tried (and failed) to run `' . $cmd . '`' . "\n\n" . implode("\n", $out));
            }
        }

        $this->response->status(303);
        $this->response->header('Location', $this->request->getScheme() . '://' . $this->request->getHostWithPort() . '/r/' . $options['repo']['ShortName']);
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

    private function syncCmdCreate($sudoUser, $pusherLocation, $depId, $logId, array $envVars)
    {
        $cmdStart = 'sudo -n -H -u %s';
        $cmdEnvs = ' ';
        $cmdCmd = '%s %s %s &>/dev/null';
        foreach ($envVars as $k => $v) {
            $cmdEnvs .= escapeshellarg($k) . '=' . escapeshellarg($v) . ' ';
        }

        $cmd = sprintf($cmdStart, escapeshellarg($sudoUser));
        $cmd .= $cmdEnvs;
        $cmd .= sprintf($cmdCmd, escapeshellarg($pusherLocation), escapeshellarg($depId), escapeshellarg($logId));

        return $cmd;
    }
}
