<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\LdapService;
use PDO;
use QL\Hal\Admin\Dashboard;
use QL\Hal\Arrangements;
use QL\Hal\Admin\ManageDeploymentsHandler;
use QL\Hal\Admin\ManageDeployments;
use QL\Hal\Admin\ManageRepositories;
use QL\Hal\Admin\ManageRepositoriesHandler;
use QL\Hal\Admin\ManageServers;
use QL\Hal\Admin\ManageServersHandler;
use QL\Hal\LoginRequired;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ServerService;
use QL\Hal\Services\UserService;
use Slim\Slim;
use Slim\Http\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

require '../vendor/autoload.php';

$app = new Slim;
session_start();

$twigEnv = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/../templates'));
$ad = new LdapService;
$db = new PDO('mysql:unix_socket=/tmp/mysql.sock;dbname=gitbertSlim;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$repos = new RepositoryService($db);
$servers = new ServerService($db);
$deployments = new DeploymentService($db);
$users = new UserService($db);

$app->add(new LoginRequired('/login', $_SESSION));

$app->get('/login',  new Login($app->response(), $twigEnv->loadTemplate('login.twig')));
$app->post('/login', new LoginHandler(
    $app->response(),
    $app->request(),
    $_SESSION,
    $ad,
    $twigEnv->loadTemplate('login.twig'),
    $users
));

$app->get('/',  new Arrangements($app->response(), $twigEnv->loadTemplate('home.twig'), $_SESSION));
$app->get('/admin',  new Dashboard($app->response(), $twigEnv->loadTemplate('admin/dashboard.twig')));

$app->get('/admin/repositories', new ManageRepositories($app->response(), $twigEnv->loadTemplate('admin/repositories.twig'), $repos));
$app->post('/admin/repositories', new ManageRepositoriesHandler(
    $app->response(),
    $app->request(),
    $twigEnv->loadTemplate('admin/repositories.twig'),
    $repos
));

$app->get('/admin/servers', new ManageServers($app->response(), $twigEnv->loadTemplate('admin/servers.twig'), $servers));
$app->post('/admin/servers', new ManageServersHandler(
    $app->response(),
    $app->request(),
    $twigEnv->loadTemplate('admin/servers.twig'),
    $servers
));

$app->get('/admin/deployments', new ManageDeployments(
    $app->response(), 
    $twigEnv->loadTemplate('admin/deployments.twig'), 
    $repos,
    $servers, 
    $deployments
));
$app->post('/admin/deployments', new ManageDeploymentsHandler(
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
    $gitDeployments = new Deployments();
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
