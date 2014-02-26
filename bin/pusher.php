#!/usr/bin/env php
<?php
// The command line program that actually pushes code.
// The goal is to eventually replace this with Capistrano.

namespace QL\Hal;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Logger;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\LogService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Sync\NotificationService;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Github\Api\Repo as GithubRepoService;
use Github\Exception\RuntimeException;

/*
// fork and exit immediately

$pid = pcntl_fork();
if ($pid === -1) {
    fwrite(STDOUT, "could not fork");
    exit(1);
}
if ($pid !== 0) {
    exit(0);
}
*/

require_once __DIR__.'/../app/bootstrap.php';

$command = new PushCommand(
    $container->get('repoService'),
    $container->get('deploymentService'),
    $container->get('logService'),
    $container->get('githubRepoService'),
    $config->get('build.dir'),
    $config->get('rsync.user')
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

    const GITHUB_USER       = 'placeholder';
    const GITHUB_PASSWORD   = 'placeholder';
    const GITHUB_HOSTNAME   = 'git';

    const EMAIL_HOSTNAME    = 'mail.example.com';
    const EMAIL_FROM        = 'placeholder@quickenloans.com';
    const EMAIL_FROM_NAME   = 'HAL 9000';
    const EMAIL_REPLY_TO    = 'placeholder@quickenloans.com';

    const LOGGER_NAME       = 'hal9000';

    private $depId;
    private $logId;
    private $logLevel;

    private $buildDir;
    private $rsyncUser;

    private $logger;
    private $notifier;

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
     *  @param GithubRepoService $github
     *  @param $buildDir
     *  @param $rsyncUser
     */
    public function __construct(
        RepositoryService $repService,
        DeploymentService $depService,
        LogService $logService,
        GithubRepoService $github,
        $buildDir,
        $rsyncUser
    ) {
        $this->repService = $repService;
        $this->depService = $depService;
        $this->logService = $logService;
        $this->github = $github;
        $this->buildDir = $buildDir;
        $this->rsyncUser = $rsyncUser;

        $this->cleanup = array();
    }

    /**
     *  Destructor
     */
    public function __destruct()
    {
        // cleanup the filesystem
        foreach ($this->cleanup as $path) {
            exec(sprintf('rm -rf %s', escapeshellarg($path)));
        }
    }

    /**
     *  Run the command, read input from STDIN
     */
    public function run($args)
    {
        $this->prepareArgs($args);

        // get database objects
        $logInfo        = $this->logService->getById($this->logId);
        $deployment     = $this->depService->getById($this->depId);
        $repo           = $this->repService->getById($deployment['RepositoryId']);

        // setup mailer, logging, and notifications
        $this->prepareNotifications(
            $logInfo['PushRepo'],
            $logInfo['Environment'],
            $logInfo['TargetServer'],
            $logInfo['PushUserName'],
            $repo['OwnerEmail'],
            $logInfo['PushBranch'],
            $logInfo['CommitSha']
        );

        if (is_null($deployment) || is_null($repo)) {
            $this->terminate('Unable to load deployment or repository from database. Wrong id?');
        }

        $path = $this->getTempDir();

        // verify repo name from github api
        // prevents conflicts because github clone urls are case sensitive, but not when called from the API...
        $this->validateGithubRepo(
            $deployment['GithubUser'],
            $deployment['GithubRepo']
        );

        $this->logger->debug('Prepared push environment', $_SERVER);
        $this->logger->debug('Selected temporary directory: '.$path);

        // clone code to the temp path
        $this->cloneGithubRepo(
            $deployment['GithubUser'],
            $deployment['GithubRepo'],
            $logInfo['CommitSha'],
            $path
        );

        // run build command
        $this->runBuildCommand($path, $repo['BuildCmd']);

        // rsync to server
        $this->runPush(
            array($path.'/'),
            $this->rsyncUser,
            $logInfo['TargetServer'],
            $logInfo['TargetPath'],
            $output,
            $command
        );

        // post push script on server?
        // ...

        $this->logger->notice('Push successful');
        $this->notifier->notifySyncFinish(true);
        exit(0);
    }

    /**
     *  Get and check command line arguments
     */
    private function prepareArgs($args)
    {
        $this->depId = isset($args[1]) ? $args[1] : null;
        $this->logId = isset($args[2]) ? $args[2] : null;

        if (is_null($this->depId) || is_null($this->logId)) {
            $this->terminate("USAGE: pusher.php DEP_ID LOG_ID [DEBUG]");
        }

        $debug = isset($args[3]) ? $args[3] : null;

        if ($debug) {
            $this->logLevel = Logger::DEBUG;
        } else {
            $this->logLevel = (self::DEBUG_DEFAULT) ? Logger::DEBUG : Logger::INFO;
        }
    }

    /**
     *  Prepare the mailer, logger, and notification services
     *
     *  @param $repo        The repository name
     *  @param $env         The deployment environment
     *  @param $server      The remote server
     *  @param $pusher      The push initiator
     *  @param $ownerEmail  The repository owner email
     *  @param $branch      The deployment repo branch
     *  @param $commit      The deployment repo commit
     */
    private function prepareNotifications($repo, $env, $server, $pusher, $ownerEmail, $branch, $commit)
    {
        $subjectLine = sprintf(
            '[%s][%s][%s][%s]',
            $repo,
            $env,
            $server,
            $pusher
        );

        $mailer = new Swift_Mailer(
            new Swift_SmtpTransport(
                self::EMAIL_HOSTNAME
            )
        );

        $email = new Swift_Message;
        $email->addTo($ownerEmail);
        $email->setFrom(self::EMAIL_FROM, self::EMAIL_FROM_NAME);
        $email->setSubject($subjectLine);
        $email->setReplyTo(self::EMAIL_REPLY_TO);

        $handler = new SwiftMailerHandler($mailer, $email, $this->logLevel);

        // setup logging
        $buffer = new BufferHandler($handler);
        $this->logger = new Logger(self::LOGGER_NAME, array($buffer));

        // setup notifications
        $this->notifier = new NotificationService(
            $this->logger,
            $email,
            $this->depService,
            $this->logService,
            $this->depId,
            $this->logId,
            $branch,
            $commit
        );
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

        try {
            $githubRepo = $this->github->show($user, $repo);
        } catch (RuntimeException $e) {
            $githubRepo = null;
        }

        if (!$githubRepo) {
            $this->logger->critical('Github user or repository appears to be invalid.');
            $this->terminate('Unable to validate github user or repository');
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
     *  Clone a Github repository and checkout a specific commit
     *
     *  @param $user    The Github user or organization (capitalization matters!)
     *  @param $repo    The Github repository name (capitalization matters)
     *  @param $commit  The commit hash to checkout
     *  @param $path    The filesystem path to clone to
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
        } else {
            $this->logger->critical(implode('\n', $out));
            $this->terminate('Error when executing repository clone!');
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

        $command = sprintf(
            'cd %s && %s 2>&1',
            $path,
            $command
        );

        $this->logger->debug('Executing '.$command);

        // run the command
        exec($command, $out, $code);

        if ($code === 0) {
            $this->logger->info('Successfully ran build command');
        } else {
            $this->logger->critical(implode('\n', $out));
            $this->terminate('Error when executing build command!');
        }
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
            $this->logger->critical(implode('\n', $out));
            $this->terminate('Error when pushing code to remote server');
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
     *  Generate, but don't create, a random directory for later use
     *
     *  @return string
     */
    private function getTempDir()
    {
        $random = '';

        while (strlen($random) < 9) {
            $random .= chr(rand(0, 1) ? rand(48, 57) : rand(97, 122));
        }

        $path = $this->buildDir.'/'.$random;

        // add path to cleanup list on destruct
        $this->cleanup[] = $path;

        return $path;
    }

    /**
     *  Terminate application
     */
    private function terminate($out = "Application terminating abnormally.")
    {
        $this->out($out);

        // notify the notifier, if it's available
        if ($this->notifier) {
            $this->notifier->notifySyncFinish(false);
        }

        exit(1);
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