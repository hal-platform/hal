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
use QL\Hal\Admin\ManageEnvironments;
use QL\Hal\Arrangements;
use QL\Hal\Admin\ManageArrangements;
use QL\Hal\Admin\ManageDeploymentsHandler;
use QL\Hal\Admin\ManageDeployments;
use QL\Hal\Admin\ManageRepositories;
use QL\Hal\Admin\ManageRepositoriesHandler;
use QL\Hal\Admin\ManageServers;
use QL\Hal\Admin\ManageServersHandler;
use QL\Hal\LoginRequired;
use QL\Hal\Services\ArrangementService;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\EnvironmentService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ServerService;
use QL\Hal\Services\UserService;
use Slim\Helper\Set;
use Slim\Slim;
use Slim\Http\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

require '../vendor/autoload.php';

$app = new Slim;
session_start();

// setup twig
$app->container->singleton('twigEnv', function () {
    return new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/../templates'));
});

// Active Directory service
$app->container->singleton('ldapService', function () {
    return new LdapService;
});

// database connection
$app->container->singleton('db', function () {
    $db = new PDO('mysql:unix_socket=/tmp/mysql.sock;dbname=gitbertSlim;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
});

// database interface services
$app->container->singleton('repoService', function (Set $container) {
    return new RepositoryService($container['db']);
});

$app->container->singleton('serverService', function (Set $container) {
    return new ServerService($container['db']);
});

$app->container->singleton('deploymentService', function (Set $container) {
    return new DeploymentService($container['db']);
});

$app->container->singleton('userService', function (Set $container) {
    return new UserService($container['db']);
});

$app->container->singleton('envService', function (Set $container) {
    return new EnvironmentService($container['db']);
});

// page definitions
$app->container->singleton('loginPage', function (Set $container) {
    return new Login(
        $container['response'],
        $container['twigEnv']->loadTemplate('login.twig')
    );
});

$app->container->singleton('loginHandlerPage', function (Set $container) {
    return new LoginHandler(
        $container['response'],
        $container['request'],
        $_SESSION,
        $container['ldapService'],
        $container['twigEnv']->loadTemplate('login.twig'),
        $container['userService']
    );
});

$app->container->singleton('arrangementsPage', function (Set $container) {
    return new Arrangements(
        $container['response'],
        $container['twigEnv']->loadTemplate('home.twig'),
        $_SESSION
    );
});

$app->container->singleton('adminDashboardPage', function (Set $container) {
    return new Dashboard(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/dashboard.twig'),
        $container['userService']
    );
});

$app->container->singleton('adminRepositoriesPage', function (Set $container) {
    return new ManageRepositories(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/repositories.twig'),
        $container['repoService']
    );
});

$app->container->singleton('adminRepositoriesHandlerPage', function (Set $container) {
    return new ManageRepositoriesHandler(
        $container['response'],
        $container['request'],
        $container['twigEnv']->loadTemplate('admin/repositories.twig'),
        $container['repoService']
    );
});

$app->container->singleton('adminServersPage', function (Set $container) {
    return new ManageServers(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/servers.twig'),
        $container['serverService'],
        $container['envService']
    );
});

$app->container->singleton('adminServersHandlerPage', function (Set $container) {
    return new ManageServersHandler(
        $container['response'],
        $container['request'],
        $container['twigEnv']->loadTemplate('admin/servers.twig'),
        $container['serverService']
    );
});

$app->container->singleton('adminDeploymentsPage', function (Set $container) {
    return new ManageDeployments(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/deployments.twig'),
        $container['repoService'],
        $container['serverService'],
        $container['deploymentService']
    );
});

$app->container->singleton('adminDeploymentsHandlerPage', function (Set $container) {
    return new ManageDeploymentsHandler(
        $container['response'],
        $container['request'],
        $container['twigEnv']->loadTemplate('admin/deployments.twig'),
        $container['deploymentService']
    );
});

$app->container->singleton('adminEnvironmentsPage', function (Set $container) {
    return new ManageEnvironments(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/environments.twig')
    );
});

$app->container->singleton('adminEnvironmentsHandlerPage', function (Set $container) {

});

// require login for all pages except /login
$app->add(new LoginRequired('/login', $_SESSION));

// default to UTF-8 character set response
$app->response()->header('Content-Type', 'text/html; charset=utf-8');

// define routes -- this should be pulled out into some config file at some point
$app->get ('/',                   function () use ($app) { call_user_func($app->arrangementsPage);             });
$app->get ('/login',              function () use ($app) { call_user_func($app->loginPage);                    });
$app->post('/login',              function () use ($app) { call_user_func($app->loginHandlerPage);             });
$app->get ('/admin',              function () use ($app) { call_user_func($app->adminDashboardPage);           });
$app->get ('/admin/envs',         function () use ($app) { call_user_func($app->adminEnvironmentsPage);        });
$app->post('/admin/envs',         function () use ($app) { call_user_func($app->adminEnvironmentsHandlerPage); });
$app->get ('/admin/repositories', function () use ($app) { call_user_func($app->adminRepositoriesPage);        });
$app->post('/admin/repositories', function () use ($app) { call_user_func($app->adminRepositoriesHandlerPage); });
$app->get ('/admin/servers',      function () use ($app) { call_user_func($app->adminServersPage);             });
$app->post('/admin/servers',      function () use ($app) { call_user_func($app->adminServersHanlderPage);      });
$app->get ('/admin/deployments',  function () use ($app) { call_user_func($app->adminDeploymentsPage);         });
$app->post('/admin/deployments',  function () use ($app) { call_user_func($app->adminDeploymentsHandlerPage);  });

// GO FORTH AND PROSPER
$app->run();
