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

//var_dump($userApi->find('bnagi'));
print_r($repoApi->tags('mnagi', 'mcp-core'));
//print_r($repoApi->commits()->compare('mnagi', 'mcp-core', 'master', 'php5.5'));

//echo $repoApi->contents()->archive('mnagi', 'mcp-core', 'tarball', 'master');
