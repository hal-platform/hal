#!/usr/bin/env php
<?php
// The command line program that actually pushes code.
// The goal is to eventually replace this with Capistrano.

namespace QL\Hal;

use DateTime;
use DateTimeZone;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Logger;
use PDO;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\LogService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Sync\NotificationService;
use QL\Hal\Sync\TemporaryDirectoryService;
use QL\Hal\Sync\TempraryDirectoryService;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\Yaml\Yaml;
use Github\Client as GithubApiClient;

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
    $container->get('logService')
);
$command->run();

class PushCommand
{
    private $depId;
    private $logId;
    private $debug;

    private $repService;
    private $depService;
    private $logService;

    public function __construct(RepositoryService $repService, DeploymentService $depService, LogService $logService)
    {
        $this->repService = $repService;
        $this->depService = $depService;
        $this->logService = $logService;
    }

    /**
     *  Run the command
     */
    public function run()
    {
        $this->getArgs();

        $deployment = $this->depService->getById($this->depId);
        $logger     = $this->logService->getById($this->logId);
        $repo       = $this->repService->getById($deployment['RepositoryId']);
    }

    /**
     *  Get and check command line arguments
     */
    private function getArgs()
    {
        $this->depId = isset($argv[1]) ? $argv[1] : null;
        $this->logId = isset($argv[2]) ? $argv[2] : null;
        $this->debug = isset($argv[3]) ? $argv[3] : null;

        if (is_null($this->depId) || is_null($this->logId)) {
            $this->terminate("USAGE: pusher.php DEP_ID LOG_ID");
        }
    }

    /**
     *  Terminate application
     */
    private function terminate($out = "Application terminating abnormally.")
    {
        $this->out($out);
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

    /**
     *  RSync to a remote host
     *
     *  rsync --recursive --delete --links <srcdir> <user>@<targethost>:<targetpath>
     *
     *  @param array $fromdir
     *  @param $touser
     *  @param $tohost
     *  @param $topath
     *  @param $output
     *  @param $command
     *  @return mixed
     */
    private function rsyncRemote(array $fromdir, $touser, $tohost, $topath, &$output, &$command)
    {
        $target = '%s@%s:%s';
        $target = sprintf($target, $touser, $tohost, $topath);
        $exclude = array('config/database.ini', 'data/');
        $ret = rsync($fromdir, $target, $exclude, $output, $command);
        return $ret;
    }

    /**
     *  RSync Files
     *
     * @param array $from
     * @param $to
     * @param array $exclude
     * @param $output
     * @param $command
     * @return mixed
     */
    private function rsync(array $from, $to, array $exclude, &$output, &$command)
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


}

/*
// rsync --recursive --delete --links <srcdir> <user>@<targethost>:<targetpath>
function rsyncToServer(array $fromdir, $touser, $tohost, $topath, &$output, &$command)
{
    $target = '%s@%s:%s';
    $target = sprintf($target, $touser, $tohost, $topath);
    $exclude = array('config/database.ini', 'data/');
    $ret = rsync($fromdir, $target, $exclude, $output, $command);
    return $ret;
}

function rsync(array $from, $to, array $exclude, &$output, &$command)
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
*/

// error if the command line args don't make sense
/*
$depId = isset($argv[1]) ? $argv[1] : null;
$logId = isset($argv[2]) ? $argv[2] : null;
$debug = isset($argv[3]) ? $argv[3] : null;
*/
/*
if (
    is_null($depId) ||
    is_null($logId)
) {
    fwrite(STDOUT, "USAGE: pusher.php DEP_ID LOG_ID\n");
    exit(1);
}

if ($debug && $debug == 'DEBUG') {
    $debug = true;
}
*/


// read in app config information
/*
$config = Yaml::parse(file_get_contents($root . '/app/config.yml'));
*/

// connect to the DB
/*
$db = new PDO(
    $config['db.dsn'],
    $config['db.user'],
    $config['db.pass'],
    [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
);
*/
/*
// lookup database entries
$repService = new RepositoryService($db);
$depService = new DeploymentService($db);
$logService = new LogService($db);
$depInfo = $depService->getById($depId);
$logInfo = $logService->getById($logId);
$repInfo = $repService->getById($depInfo['RepositoryId']);
*/

// Maybe check arrays and fork here instead of above to catch possible error
// situations with passing the wrong id's on the command line

// start up logger and notification service

$subjectLine = '[%s][%s][%s][%s]';
$subjectLine = sprintf(
    $subjectLine,
    $logInfo['PushRepo'],
    $logInfo['Environment'],
    $logInfo['TargetServer'],
    $logInfo['PushUserName']
);

$mailer = new Swift_Mailer(new Swift_SmtpTransport('mail.example.com'));
$email = new Swift_Message;
$email->addTo($repInfo['OwnerEmail']);
$email->setFrom('hal9000@quickenloans.com', "HAL 9000");
$email->setSubject($subjectLine);
$email->setReplyTo('mattnagi@quickenloans.com');

if ($debug) {
    $logLevel = Logger::DEBUG;
} else {
    $logLevel = Logger::INFO;
}
$handler = new SwiftMailerHandler($mailer, $email, $logLevel);

$buffer = new BufferHandler($handler);
$logger = new Logger('hal9000', [$buffer]);

// log environment for now
$logger->debug("pusher script environment", $_SERVER);

$notifier = new NotificationService(
    $logger,
    $email,
    $depService,
    $logService,
    $depId,
    $logId,
    $logInfo['PushBranch'],
    $logInfo['CommitSha']
);

// choose a temporary working space
$logger->info("Creating temporary directory");
$tmpDir = new TemporaryDirectoryService($config['build.dir']);
if ($tmpDir->error()) {
    $logger->critical($tmpDir->error());
    $notifier->notifySyncFinish(false);
    exit(1);
}
$logger->debug("Chose temporary directory: ".$tmpDir->dir());

// prepare to clone
$logger->info("Preparing to clone repository");
$command = sprintf(
    'git clone -v http://%s:%s@git/%s/%s.git %s && cd %s && git checkout -f %s',
    'placeholder',
    'placeholder',
    $depInfo['GithubUser'],
    $depInfo['GithubRepo'],
    $tmpDir->dir(),
    $tmpDir->dir(),
    $logInfo['CommitSha']
);
die(var_dump($command));


/*
// create temporary working space

$logger->info("Creating temporary directory");
$tmpDir = new TemporaryDirectoryService($config['build.dir']);
if ($tmpDir->error()) {
    $logger->critical($tmpDir->error());
    $notifier->notifySyncFinish(false);
    exit(1);
}
$logger->debug("Temporary directory created: " . $tmpDir->dir());

// get code

$curlCmd = 'curl -s -S -u %s:%s -L http://git/api/v3/repos/%s/%s/tarball/%s | tar -x -C %s';
$curlCmd = sprintf(
    $curlCmd,
    'placeholder',
    'placeholder',
    $depInfo['GithubUser'],
    $depInfo['GithubRepo'],
    $logInfo['CommitSha'],
    $tmpDir->dir()
);
$logger->info("Executing $curlCmd");
exec($curlCmd, $out, $ret);
if ($ret !== 0) {
    $logger->critical(implode("\n", $out));
    $notifier->notifySyncFinish(false);
    exit(1);
}
$extracted = glob(sprintf($tmpDir->dir() . '/%s', $depInfo['GithubUser']) . '*', GLOB_ONLYDIR | GLOB_MARK);
$logger->debug("Glob result", $extracted);
if (!$extracted) {
    $logger->critical("Code extraction failed in an unexpected fashion.");
    $notifier->notifySyncFinish(false);
    exit(1);
}
$extracted = $extracted[0];
$logger->debug("Decision on extracted dir", ["dir" => $extracted]);
*/


// build code

if ($repInfo['BuildCmd']) {
    // possibly need to dump HAL_ env vars to environment here?
    $cmd = $repInfo['BuildCmd'] . " 2>&1";
    $logger->info("Executing " . $cmd);
    chdir($extracted);
    exec($cmd, $out, $ret);
    if ($ret !== 0) {
        $logger->error(implode("\n", $out));
        $notifier->notifySyncFinish(false);
        exit(1);
    }
} else {
    $logger->info("No build command specified for repository, skipping.");
}

// rsync to server

$ret = rsyncToServer([$extracted . '/'], $config['rsync.user'], $logInfo['TargetServer'], $logInfo['TargetPath'], $out, $cmd);
$logger->info("Executing " . $cmd);
if ($ret !== 0) {
    $logger->error(implode("\n", $out));
    $notifier->notifySyncFinish(false);
    exit(1);
}

// todo: do post push script on target server...

$logger->notice('PUSH SUCCESS!');
$notifier->notifySyncFinish(true);
exit(0);
