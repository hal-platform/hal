#!/usr/bin/env php
<?php
// The command line program that actually pushes code.
// The goal is to eventually replace this with Capistrano.

namespace QL\Hal;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\SwiftMailerHandler;
use Psr\Log\LoggerInterface as Logger;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\GithubService;
use QL\Hal\Services\LogService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Sync\NotificationService;
use QL\Hal\Mail\Message;

use Exception;
use DateTime;
use DateTimeZone;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__.'/../app/bootstrap.php';

if ($config->get('build.async')) {
    // fork and exit immediately
    $pid = pcntl_fork();
    if ($pid === -1) {
        fwrite(STDOUT, "could not fork");
        exit(1);
    } elseif ($pid !== 0) {
        exit(0);
    }
}

$container->get('github.httpClient')->setCache($container->get('github.memory-cache'));
$command = new PushCommand(
    $container->get('repoService'),
    $container->get('deploymentService'),
    $container->get('logService'),
    $container->get('github'),
    $container->get('buildLogger'),
    $container->get('buildMessage'),
    $config->get('build.dir'),
    $config->get('rsync.user'),
    $config->get('email.overide')
);
$command->run($argv);

/**
 *  Push Command Class
 *
 *  Should be broken out at some point. Also, should have all dependencies passed... here be news
 */
class PushCommand
{
    const DEBUG_DEFAULT     = true;

    const FS_DIRECTORY_PREFIX   = 'hal9000-build-';

    const OUT_SUCCESS           = 'Exiting with success state.';
    const OUT_FAILURE           = 'Exiting with failure state.';

    // WTB ORM
    const KEY_INFO_REPO         = 'PushRepo';
    const KEY_INFO_ENV          = 'Environment';
    const KEY_INFO_SERVER       = 'TargetServer';
    const KEY_INFO_SERVER_PATH  = 'TargetPath';
    const KEY_INFO_PUSHER       = 'PushUserName';
    const KEY_INFO_BRANCH       = 'PushBranch';
    const KEY_INFO_COMMIT       = 'CommitSha';
    const KEY_DEP_REPO_ID       = 'RepositoryId';
    const KEY_DEP_GH_USER       = 'GithubUser';
    const KEY_DEP_GH_REPO       = 'GithubRepo';
    const KEY_REPO_OWNER        = 'OwnerEmail';
    const KEY_REPO_CMD          = 'BuildCmd';
    const KEY_REPO_PP_CMD       = 'PostPushCmd';

    const GITHUB_USER       = 'placeholder';
    const GITHUB_PASSWORD   = 'placeholder';
    const GITHUB_HOSTNAME   = 'git';

    private $depId;
    private $logId;

    private $buildDir;
    private $rsyncUser;

    private $logger;
    private $message;

    private $branch;
    private $commit;

    private $repService;
    private $depService;
    private $logService;
    private $github;

    private $cleanup;

    /**
     *  Constructor
     *
     *  @param RepositoryService $repService
     *  @param DeploymentService $depService
     *  @param LogService $logService
     *  @param GithubService $github
     *  @param Logger $logger
     *  @param Message $message
     *  @param $buildDir
     *  @param $rsyncUser
     *  @param $emailOveride
     */
    public function __construct(
        RepositoryService $repService,
        DeploymentService $depService,
        LogService $logService,
        GithubService $github,
        Logger $logger,
        Message $message,
        $buildDir,
        $rsyncUser,
        $emailOveride
    ) {
        $this->repService = $repService;
        $this->depService = $depService;
        $this->logService = $logService;
        $this->github = $github;
        $this->buildDir = $buildDir;
        $this->rsyncUser = $rsyncUser;
        $this->emailOveride = $emailOveride;

        $this->logger = $logger;
        $this->message = $message;

        $this->cleanup = array();

        // debug, catch exceptions globally
        set_exception_handler(function (Exception $e) use ($logger) {
            $this->logger->critical("Unhandled exception encountered. This is a problem.");
            $this->logger->critical($e->getMessage(), $e->getTrace());
            $this->failure();
        });
    }

    /**
     *  Destructor
     */
    public function __destruct()
    {
        $this->cleanupTempDirs();
    }

    /**
     *  Run the command, read input from STDIN
     */
    public function run($args)
    {
        $this->prepareArgs($args);

        $this->logger->debug('Prepared push environment', $_SERVER);

        $objInfo   = $this->logService->getById($this->logId);
        $objDep    = $this->depService->getById($this->depId);

        if (is_null($objInfo) || is_null($objDep)) {
            $this->failure('Unable to lookup logInfo or deployment from database. Wrong ID?');
        }

        $objRepo   = $this->repService->getById($objDep[self::KEY_DEP_REPO_ID]);

        if (is_null($objRepo)) {
            $this->failure('Unable to lookup repository from database. Wrong ID?');
        }

        $this->logger->debug('Found logInfo:', $objInfo);
        $this->logger->debug('Found deployment:', $objDep);
        $this->logger->debug('Found repository:', $objRepo);

        // lookup build details
        // WTB ORM
        $repo           = $objInfo[self::KEY_INFO_REPO];
        $env            = $objInfo[self::KEY_INFO_ENV];
        $server         = $objInfo[self::KEY_INFO_SERVER];
        $serverPath     = $objInfo[self::KEY_INFO_SERVER_PATH];
        $pusher         = $objInfo[self::KEY_INFO_PUSHER];
        $ghUser         = $objDep[self::KEY_DEP_GH_USER];
        $ghRepo         = $objDep[self::KEY_DEP_GH_REPO];
        $owner          = $objRepo[self::KEY_REPO_OWNER];
        $buildCmd       = $objRepo[self::KEY_REPO_CMD];
        $postPushCmd    = $objRepo[self::KEY_REPO_PP_CMD];

        $this->branch   = $objInfo[self::KEY_INFO_BRANCH];
        $this->commit   = $objInfo[self::KEY_INFO_COMMIT];

        // validate and prepare
        $this->message->setBuildDetails($owner, $repo, $env, $server, $pusher);
        $this->validateGithubRepo($ghUser, $ghRepo);

        // determine build id and path
        $buildId = $this->getBuildId();
        $buildPath = $this->getTempDir($buildId);

        // download and build
        $location = $this->downloadGithubRepo($ghUser, $ghRepo, $this->commit, $buildPath);
        $this->runBuildCommand($location, $buildCmd);

        // validate remote hostname
        if (($hostname = $this->validateHostname($server)) === false) {
            $this->failure("Cannot resolve hostname $hostname.");
        }

        // write build properties
        $this->dumpBuildProps(
            $location,
            $buildId,
            sprintf(
                'http://git/%s/%s',
                $ghUser,
                $ghRepo
            ),
            $env,
            $pusher,
            $this->branch,
            $this->commit
        );

        // push to server
        $this->runPush(
            array($location.'/'),
            $this->rsyncUser,
            $hostname,
            $serverPath,
            $output,
            $command
        );

        // post push script
        $this->runPostPush(
            $hostname,
            $this->rsyncUser,
            $serverPath,
            $postPushCmd,
            [
                'HAL_HOSTNAME'      => $hostname,
                'HAL_ENVIRONMENT'   => $env,
                'HAL_PATH'          => $serverPath,
                'HAL_COMMIT'        => $this->commit,
                'HAL_GITREF'        => $this->branch,
                'HAL_BUILDID'       => $buildId
            ]
        );

        $this->success('Push successful.');
    }

    /**
     *  Get and check command line arguments
     */
    private function prepareArgs($args)
    {
        $this->depId = isset($args[1]) ? $args[1] : null;
        $this->logId = isset($args[2]) ? $args[2] : null;

        if (is_null($this->depId) || is_null($this->logId)) {
            $this->failure("USAGE: pusher.php DEP_ID LOG_ID [DEBUG]");
        }
    }

    /**
     *  Validate the Github user and repository and fix any capitalization problems
     *
     *  @param $user
     *  @param $repo
     */
    private function validateGithubRepo(&$user, &$repo)
    {
        $this->logger->info('Validating Github user and repository');

        if (!$githubRepo = $this->github->repository($user, $repo)) {
            $this->logger->critical('Github user or repository appears to be invalid.');
            $this->failure('Unable to validate github user or repository');
        } else {
            $this->logger->info('Github user and repository were validated successfully');
        }

        // check for semantic mistakes and correct them
        if ($user !== $githubRepo['owner']['login'] || $repo !== $githubRepo['name']) {
            $this->logger->info('Looks like the Github user or repository were capitalized incorrectly... fixing.');
            $this->logger->info(
                sprintf(
                    'Provided values --- User: %s Repository: %s',
                    $user,
                    $repo
                )
            );
            $user = $githubRepo['owner']['login'];
            $repo = $githubRepo['name'];
            $this->logger->info(
                sprintf(
                    'Corrected values --- User: %s Repository: %s',
                    $user,
                    $repo
                )
            );
        }
    }

    /**
     *  Download and extract a specific Github repository commit
     *
     *  @param string $user     The Github user or organization (capitalization matters!)
     *  @param string $repo     The Github repository name (capitalization matters)
     *  @param string $commit   The commit to retrieve
     *  @param string $path     The filesystem path to extract to
     *  @return string          Path where projects files can be found
     */
    private function downloadGithubRepo($user, $repo, $commit, $path)
    {
        $this->logger->info('Downloading repository to temporary directory');

        $file = "$path.tar.gz";

        // build archive download, extract, and cleanup command
        $command = sprintf(
            '%s && %s && %s && %s',
            // download archive
            sprintf(
                'curl -s -S -u %s:%s -L http://%s/api/v3/repos/%s/%s/tarball/%s -o %s',
                self::GITHUB_USER,
                self::GITHUB_PASSWORD,
                self::GITHUB_HOSTNAME,
                $user,
                $repo,
                $commit,
                $file
            ),
            // create temporary directory
            sprintf(
                'mkdir %s',
                $path
            ),
            // extract archive
            sprintf(
                'tar -x -z --directory=%s -f %s',
                $path,
                $file
            ),
            // delete archive
            sprintf(
                'rm -f %s',
                $file
            )
        );

        $this->logger->debug('Executing command: '.$command);

        // run the command
        exec($command, $out, $code);

        if ($code === 0) {
            $this->logger->info('Repository successfully downloaded into '.$path);
        } else {
            $this->logger->critical('Error when executing repository downlaod', $out);
            $this->failure('Error when executing repository download');
        }

        // check downloaded files
        $results = glob(sprintf('%s/%s-%s-*', $path, $user, $repo), GLOB_ONLYDIR | GLOB_MARK);

        if (is_array($results) && count($results) == 1) {
            $location = reset($results);
            $this->logger->info('Repository successfully extracted to '.$location);
            return $location;
        } else {
            $this->logger->critical('Unable to locate extracted repository in '.$path);
            $this->failure('Error when verifying extracted repository.');
        }
    }

    /**
     *  Clone a Github repository and checkout a specific commit
     *
     *  $this->downloadGithubRepo() should be used instead, wherever possible to minimize the size
     *  of transferred files from Github.
     *
     *  @param $user    The Github user or organization (capitalization matters!)
     *  @param $repo    The Github repository name (capitalization matters)
     *  @param $commit  The treeish to checkout
     *  @param $path    The filesystem path to clone to
     *  @return string  Path where projects files can be found
     */
    private function cloneGithubRepo($user, $repo, $commit, $path)
    {
        $this->logger->info('Cloning repository to temporary directory');

        $command = sprintf(
            'git clone http://%s:%s@%s/%s/%s.git %s && cd %s && git checkout -f %s',
            self::GITHUB_USER,
            self::GITHUB_PASSWORD,
            self::GITHUB_HOSTNAME,
            $user,
            $repo,
            $path,
            $path,
            $commit
        );

        $this->logger->debug('Executing command: '.$command);

        // run the command
        exec($command, $out, $code);

        if ($code === 0) {
            $this->logger->info('Repository successfully cloned to '.$path);
            return $path;
        } else {
            $this->logger->critical('Error when executing repository clone!', $out);
            $this->failure('Error when executing repository clone!');
        }
    }

    /**
     *  Run the application build command (if any)
     *
     *  @param $path
     *  @param $command
     */
    private function runBuildCommand($path, $command)
    {
        $this->logger->info('Running build command');

        if (!$command) {
            $this->logger->info('No build command specified, skipping.');
            return;
        }

        exec('env', $out, $code);
        $this->logger->debug('Verifying environment variables...', $out);

        $command = sprintf(
            'cd %s && %s 2>&1',
            $path,
            $command
        );

        $this->logger->debug('Executing '.$command);

        // run the command
        exec($command, $out, $code);

        if (is_array($out) && count($out) > 0) {
            $this->logger->info('Build command generated output...', $out);
        }

        if ($code === 0) {
            $this->logger->info('Successfully ran build command');
        } else {
            $this->failure('Error when executing build command!');
        }
    }

    /**
     *  Validate a hostname
     *
     *  @param string $hostname
     *  @return string|false
     */
    private function validateHostname($hostname)
    {
        $this->logger->info('Validating hostname...');

        if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->logger->info("Hostname appears to be an IP, skipping check.");
        } elseif ($hostname === gethostbyname($hostname)) {
            $this->logger->info("Cannot resolve hostname $hostname trying $hostname.rockfin.com instead.");
            $hostname = "$hostname.rockfin.com";
            if ($hostname === gethostbyname($hostname)) {
                $this->logger->crit("Cannot resolve hostname $hostname");
                return false;
            }
        }

        return $hostname;
    }

    /**
     *  Push code to remote host
     *
     *  @param array $fromdir
     *  @param $touser
     *  @param $tohost
     *  @param $topath
     *  @param $output
     *  @param $command
     *  @return mixed
     */
    private function runPush(array $fromdir, $touser, $tohost, $topath, &$output = null, &$command = null)
    {
        $this->logger->info('Preparing to sync code to remote server...');
        $this->logger->info('Rsyncing code to remote server');

        $target = sprintf(
            '%s@%s:%s',
            $touser,
            $tohost,
            $topath
        );

        $exclude = array('config/database.ini', 'data/');

        $code = $this->rsync($fromdir, $target, $exclude, $out, $command);

        $this->logger->debug('Executing command: '.$command);

        if ($code === 0) {
            $this->logger->info('Successfully pushed code to remote server');
        } else {
            $this->logger->critical('Error when pushing code to remote server', $out);
            $this->failure('Error when pushing code to remote server');
        }
    }

    /**
     *  RSync Files
     *
     *  rsync --recursive --delete --links <srcdir> <user>@<targethost>:<targetpath>
     *
     *  @param array $from
     *  @param $to
     *  @param array $exclude
     *  @param $output
     *  @param $command
     *  @return mixed
     */
    private function rsync(array $from, $to, array $exclude, &$output = null, &$command = null)
    {
        $from = array_map('escapeshellarg', $from);
        $from = implode(' ', $from);
        $excludeFlag = '';
        if ($exclude) {
            foreach ($exclude as $item) {
                $excludeFlag .= ' --exclude=' . escapeshellarg($item);
            }
        }
        $cmd = 'rsync -e "ssh -o BatchMode=yes" -r -l -p -g -o -D -c -v %s --delete-after %s %s 2>&1';
        $cmd = sprintf($cmd, $excludeFlag, $from, escapeshellarg($to));
        $command = $cmd;
        exec($cmd, $output, $ret);
        return $ret;
    }

    /**
     *  Run the post push command
     *
     *  @param $hostname    The remote server hostname
     *  @param $user        The user to authenticate as
     *  @param $path        The remote path
     *  @param $command     The command to execute
     *  @param array $env   An array of environment variables
     */
    private function runPostPush($hostname, $user, $path, $command, array $env = [])
    {
        $this->logger->info("Running post push command on remote server");

        if (!$command) {
            $this->logger->info('No post push command specified, skipping.');
            return;
        }

        // pass environment variables
        $envSetters = '';
        foreach ($env as $key => $value) {
            $envSetters .= sprintf('%s="%s" ', $key, $value);
        }

        $command = sprintf(
            "ssh %s@%s '%s && cd %s && %s 2>&1'",
            $user,
            $hostname,
            $envSetters,
            $path,
            $command
        );

        $this->logger->debug('Executing '.$command);

        // run the command
        exec($command, $out, $code);

        if (is_array($out) && count($out) > 0) {
            $this->logger->info('Command generated output...', $out);
        }

        if ($code === 0) {
            $this->logger->info('Successfully ran post push command');
        } else {
            $this->failure('Error when executing post push command!');
        }
    }

    /**
     *  Dump the build properties to a file in the build directory
     *
     *  @param string $dir      The build directory
     *  @param string $id       The build ID
     *  @param string $source   The source of the code being built
     *  @param string $env      The environment being built for
     *  @param string $user     The user building
     *  @param string $branch   The branch name being built
     *  @param string $commit   The commit hash being built
     */
    private function dumpBuildProps($dir, $id, $source, $env, $user, $branch, $commit)
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $props = [
            'id'        => $id,
            'source'    => $source,
            'env'       => $env,
            'user'      => $user,
            'branch'    => $branch,
            'commit'    => $commit,
            'date'      => $now->format('c')
        ];

        $this->logger->info('Writing build properties...', $props);

        $file = sprintf(
            '%s%s%s',
            $dir,
            DIRECTORY_SEPARATOR,
            'build.hal9000.yml'
        );

        $status = file_put_contents($file, Yaml::dump($props));

        if ($status) {
            $this->logger->info('Wrote build details to '.$file);
        } else {
            $this->failure('Unable to write build details to '.$file);
        }
    }

    /**
     *  Get a unique build id
     *
     *  @return string
     */
    private function getBuildId()
    {
        $random = '';

        while (strlen($random) < 9) {
            $random .= chr(rand(0, 1) ? rand(48, 57) : rand(97, 122));
        }

        return $random;
    }

    /**
     *  Generate, but don't create, a random directory for later use
     *
     *  @param string $id   The build id
     *  @return string
     */
    private function getTempDir($id)
    {
        $path = $this->buildDir.'/'.self::FS_DIRECTORY_PREFIX.$id;

        // add path to cleanup list on destruct
        $this->cleanup[] = $path;

        return $path;
    }

    /**
     *  Cleanup the filesystem
     */
    private function cleanupTempDirs()
    {
        foreach ($this->cleanup as $path) {
            exec(sprintf('rm -rf %s*', escapeshellarg($path)));
        }
    }

    /**
     *  Exit the process with a success state
     *
     *  @param null|string $out
     */
    private function success($out = '')
    {
        $this->terminate(true, $out);
    }

    /**
     *  Exit the process with a failure state
     *
     *  @param null|string $out
     */
    private function failure($out = '')
    {
        $this->terminate(false, $out);
    }

    /**
     *  Complete execution and exit the process
     *
     *  @param bool $status
     *  @param string $out
     */
    private function terminate($status, $out)
    {
        $this->out($out);
        $this->message->setBuildResult($status);

        if ($status) {
            if ($out) {
                $this->logger->info($out);
            }
            $this->logger->info(self::OUT_SUCCESS);
            $this->out(self::OUT_SUCCESS);
            $message = DeploymentService::STATUS_DEPLOYED;
        } else {
            if ($out) {
                $this->logger->critical($out);
            }
            $this->logger->critical(self::OUT_FAILURE);
            $this->out(self::OUT_FAILURE);
            $message = DeploymentService::STATUS_ERROR;
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->depService->update($this->depId, $message, $this->branch, $this->commit, $now);
        $this->logService->update($this->logId, $message, $now);

        exit(($status) ? 0 : 1);
    }

    /**
     *  Send a message to STDOUT
     *
     *  @param $out
     */
    private function out($out)
    {
        fwrite(STDOUT, "$out\n");
    }
}
