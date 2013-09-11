<?php
require __DIR__ . '/../vendor/autoload.php';

use Github\Client;

$c = new Client;
$c->setOption('base_url', 'http://git/api/v3/');
$c->authenticate('placeholder', null, Client::AUTH_HTTP_TOKEN);
/** @var \Github\Api\Repo $repoApi */
$repoApi = $c->api('repo');
/** @var \Github\Api\User $userApi */
$userApi = $c->api('user');

var_dump($userApi->find('bnagi'));
var_dump($repoApi->branches('mnagi', 'mcp-core'));
