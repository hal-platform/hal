#!/usr/bin/env php
<?php
// The command line program that actually pushes code.
// The goal is to eventually replace this with Capistrano.

namespace QL\Hal;

use DateTime;
use DateTimeZone;
use PDO;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\LogService;

$pid = pcntl_fork();
if ($pid === -1) {
    fwrite(STDOUT, "could not fork");
    exit(1);
}
if ($pid !== 0) {
    exit(0);
}

require_once __DIR__ . '/../vendor/autoload.php';

//sleep(5);
//$out = fopen(__DIR__ . '/pusher.log', 'a');
$out = STDERR;

$configLoader = new ConfigReader;
$config = $configLoader->load(__DIR__ . '/../conf.ini');

$db = new PDO(
    $config['db_dsn'],
    $config['db_user'],
    $config['db_pass'],
    [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
);

$depId = isset($argv[1]) ? $argv[1] : null;
$logId = isset($argv[2]) ? $argv[2] : null;

if (
    is_null($depId) ||
    is_null($logId)
) {
    fwrite($out, "USAGE: pusher.php DEP_ID LOG_ID\n");
    exit(1);
}

$depService = new DeploymentService($db);
$logService = new LogService($db);
$depInfo = $depService->getById($depId);
$logInfo = $logService->getById($logId);

// get code
// build code
// rsync to server
// 'splain what happened

$now = new DateTime('now', new DateTimeZone('UTC'));
$depService->update($depId, DeploymentService::STATUS_DEPLOYED, $logInfo['PushBranch'], $logInfo['CommitSha'], $now);
$logService->update($logId, DeploymentService::STATUS_DEPLOYED, $now);

/*
fwrite($out, print_r($_SERVER, true));
fwrite($out, print_r($_ENV, true));
fwrite($out, print_r($depInfo, true));
fwrite($out, print_r($logInfo, true));
*/
