<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */


namespace QL\GitBert2;

use PDO;
use QL\GitBert2\Services\DeploymentService;
use QL\GitBert2\Services\RepositoryService;
use QL\GitBert2\Services\ServerService;
use QL\GitBert2\Services\UserService;
use Slim;
use Slim\Http\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;
use MCP\Corp\Account\LdapService;
use QL\GitBert2\GBLoginRequired;

require '../vendor/autoload.php';

$app = new Slim\Slim();
session_start();

$twigEnv = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/../templates'));
$ad = new LdapService;
$db = new PDO('mysql:host=localhost;dbname=gitbertSlim;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$repos = new RepositoryService($db);
$servers = new ServerService($db);
$deployments = new DeploymentService($db);
$users = new UserService($db);

$app->add(new GBLoginRequired('/login', $_SESSION));

$app->get('/login',  new GBLogin($app->response(), $twigEnv->loadTemplate('login.twig')));
$app->post('/login', new GBLoginHandler(
    $app->response(),
    $app->request(),
    $_SESSION,
    $ad,
    $twigEnv->loadTemplate('login.twig'),
    $users
));

$app->get('/',  new GBHome($app->response(), $twigEnv->loadTemplate('home.twig'), $_SESSION));
$app->get('/adminMain',  new GBAdminMain($app->response(), $twigEnv->loadTemplate('admin/adminIndex.twig')));

$app->get('/repositories', new GBRepositories($app->response(), $twigEnv->loadTemplate('admin/repositories.twig'), $repos));
$app->post('/repositories', new GBRepositoriesHandler(
    $app->response(),
    $app->request(),
    $twigEnv->loadTemplate('admin/repositories.twig'),
    $repos
));

$app->get('/servers', new GBServers($app->response(), $twigEnv->loadTemplate('admin/servers.twig'), $servers));
$app->post('/servers', new GBServersHandler(
    $app->response(),
    $app->request(),
    $twigEnv->loadTemplate('admin/servers.twig'),
    $servers
));

$app->get('/deployments', new GBDeployments(
    $app->response(), 
    $twigEnv->loadTemplate('admin/deployments.twig'), 
    $repos, 
    $servers, 
    $deployments
));
$app->post('/deployments', new GBDeploymentsHandler(
    $app->response(),
    $app->request(),
    $twigEnv->loadTemplate('admin/deployments.twig'),
    $deployments
));

/*
$app->get('/permissionsMain', new GBPermissions($app->response(), $twigEnv->loadTemplate('admin/permissionsMain.twig'), $repos));
$app->get('/permissionsGrant', new GBPermissionsGrant(
    $app->response(), 
    $app->request(),
    $twigEnv->loadTemplate('admin/permissionsGrant.twig'),
    $repos,
    $deployments,
    $users
));
*/

/*
$app->get('/push', function() use ($app) {
    $gitDeployments = new GBDeployments();
    $listOfDeployments = $gitDeployments->listDeployments();
    $servers = array();
    $repositories = array();
    $deployments = array();
    foreach ($listOfDeployments as $idx => $deployment) {
        $servers[$deployment['ServerId']] = array(
            'ServerId' => $deployment['ServerId'],
            'HostName' => $deployment['HostName']
        );
        $repositories[$deployment['RepoId']] = array(
            'RepoId' => $deployment['RepoId'],
            'ShortName' => $deployment['ShortName']
        );
        $deployments[$deployment['ServerId'] . '_' . $deployment['RepoId']] = array(
            'DeploymentId' => $deployment['DeploymentId'],
            'CurBranch' => $deployment['CurBranch']
        );
    } 
    $app->render('/push/index.html', array('deployments' => $deployments, 'repositories' => $repositories, 'servers' => $servers));
});

$app->get('/push/deployment_status/:DeploymentId', function($DeploymentId) use ($app) {
    $app->response()->header('Content-Type', 'application/json;charset=utf-8');
    print json_encode(array('status' => 1));
});
*/

$app->run();
