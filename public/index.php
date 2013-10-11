<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Github\Client;
use MCP\Corp\Account\LdapService;
use PDO;
use QL\Hal\Admin\Dashboard;
use QL\Hal\Admin\ManageEnvironments;
use QL\Hal\Admin\ManageEnvironmentsHandler;
use QL\Hal\Admin\ManageDeploymentsHandler;
use QL\Hal\Admin\ManageRepositories;
use QL\Hal\Admin\ManageRepositoriesHandler;
use QL\Hal\Admin\ManageServers;
use QL\Hal\Admin\ManageServersHandler;
use QL\Hal\Admin\ManageArrangements;
use QL\Hal\Admin\ManageArrangementsHandler;
use QL\Hal\Admin\ManageDeployments;
use QL\Hal\Admin\ManageUsers;
use QL\Hal\Services\ArrangementService;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\EnvironmentService;
use QL\Hal\Services\LogService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ServerService;
use QL\Hal\Services\UserService;
use Slim\Helper\Set;
use Slim\Slim;
use Slim\Http\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

require '../vendor/autoload.php';

$configLoader = new ConfigReader;
$config = $configLoader->load(__DIR__ . '/../conf.ini');
$app = new Slim($config);

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
$app->container->singleton('db', function (Set $container) {
    $db = new PDO(
        $container['settings']['db_dsn'],
        $container['settings']['db_user'],
        $container['settings']['db_pass']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
});

// database interface services
$app->container->singleton('repoService', function (Set $container) {
    return new RepositoryService($container['db']);
});

$app->container->singleton('github', function (Set $container) {
    $githubApiClient = new Client;
    $githubApiClient->setOption('base_url', $container['settings']['github_baseurl']);
    $githubApiClient->authenticate($container['settings']['github_token'], null, Client::AUTH_HTTP_TOKEN);
    return $githubApiClient;
});

$app->container->singleton('githubRepoService', function (Set $container) {
    return $container['github']->api('repo');
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

$app->container->singleton('arrService', function (Set $container) {
    return new ArrangementService($container['db']);
});

$app->container->singleton('logService', function (Set $container) {
    return new LogService($container['db']);
});

$app->container->singleton('syncOptions', function (Set $container) {
    return new SyncOptions(
        $container['repoService'],
        $container['deploymentService'],
        $container['githubRepoService']
    );
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

$app->container->singleton('userPage', function (Set $container) {
    return new Users(
        $container['response'],
        $container['twigEnv']->loadTemplate('users.twig'),
        $container['userService']
    );
});

$app->container->singleton('landingPage', function (Set $container) {
    return new Landing(
        $container['response'],
        $container['twigEnv']->loadTemplate('home.twig'),
        $container['arrService']
    );
});

$app->container->singleton('arrangementsPage', function (Set $container) {
    return new Arrangements(
        $container['response'],
        $container['twigEnv']->loadTemplate('arrangement.twig'),
        $container['arrService'],
        $container['repoService']
    );
});

$app->container->singleton('repositoryPage', function (Set $container) {
    return new Repository(
        $container['response'],
        $container['twigEnv']->loadTemplate('repository.twig'),
        $container['repoService'],
        $container['deploymentService'],
        $container['serverService']
    );
});

$app->container->singleton('adminDashboardPage', function (Set $container) {
    return new Dashboard(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/dashboard.twig'),
        $container['repoService'],
        $container['userService']
    );
});

$app->container->singleton('adminRepositoriesPage', function (Set $container) {
    return new ManageRepositories(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/repositories.twig'),
        $container['repoService'],
        $container['arrService']
    );
});

$app->container->singleton('adminRepositoriesHandlerPage', function (Set $container) {
    return new ManageRepositoriesHandler(
        $container['response'],
        $container['request'],
        $container['twigEnv']->loadTemplate('admin/repositories.twig'),
        $container['repoService'],
        $container['arrService']
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
        $container['serverService'],
        $container['envService']
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
        $container['repoService'],
        $container['serverService'],
        $container['deploymentService']
    );
});

$app->container->singleton('adminArrangementsPage', function (Set $container) {
    return new ManageArrangements(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/arrangements.twig'),
        $container['arrService']
    );
});

$app->container->singleton('adminArrangementsHandlerPage', function(Set $container) {
    return new ManageArrangementsHandler(
        $container['response'],
        $container['request'],
        $container['twigEnv']->loadTemplate('admin/arrangements.twig'),
        $container['arrService']
    );
});

$app->container->singleton('adminEnvironmentsPage', function (Set $container) {
    return new ManageEnvironments(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/environments.twig'),
        $container['envService']
    );
});

$app->container->singleton('adminEnvironmentsHandlerPage', function (Set $container) {
    return new ManageEnvironmentsHandler(
        $container['response'],
        $container['request'],
        $container['twigEnv']->loadTemplate('admin/environments.twig'),
        $container['envService']
    );
});

$app->container->singleton('adminUsersPage', function (Set $container) {
    return new ManageUsers(
        $container['response'],
        $container['twigEnv']->loadTemplate('admin/users.twig'),
        $container['userService']
    );
});

$app->container->singleton('syncPage', function (Set $container) {
    return new SyncPage(
        $container['request'],
        $container['response'],
        $container['twigEnv']->loadTemplate('sync-code.twig'),
        $container['syncOptions']
    );
});

$app->container->singleton('syncHandler', function (Set $container) {
    return new SyncHandler(
        $container['request'],
        $container['response'],
        $container['twigEnv']->loadTemplate('sync-code.twig'),
        $container['syncOptions'],
        $container['logService'],
        $container['deploymentService'],
        $_SESSION,
        '/Users/bschiefer/skeletor_root/hal/bin/pusher.php'
    );
});

// require login for all pages except /login
$app->add(new LoginRequired('/login', $_SESSION));

// default to UTF-8 character set response
$app->response()->header('Content-Type', 'text/html; charset=utf-8');

// define user facing routes
#$app->get ('/',                   function () use ($app) { call_user_func($app->arrangementsPage);             });
$app->get ('/login',              function () use ($app) { call_user_func($app->loginPage);                    });
$app->post('/login',              function () use ($app) { call_user_func($app->loginHandlerPage);             });
$app->get ('/u/:id',              function ($id) use ($app) { call_user_func($app->userPage, $id, $app);       });
$app->get ('/',                   function () use ($app) { call_user_func($app->landingPage);             });
$app->get ('/a/:shortName',       function ($shortName) use ($app) { call_user_func($app->arrangementsPage, $shortName, $app); });
$app->get ('/r/:shortName',       function ($shortName) use ($app) { call_user_func($app->repositoryPage, $shortName, [$app, 'notFound']);   });
$app->get ('/r/:shortName/sync',  function ($shortName) use ($app) { call_user_func($app->syncPage, $shortName, [$app, 'notFound']); });
$app->post('/r/:shortname/sync',  function ($shortName) use ($app) { call_user_func($app->syncHandler, $shortName, [$app, 'notFound']); });

// admin page routes
$app->get ('/admin',              function () use ($app) { call_user_func($app->adminDashboardPage);           });
$app->get ('/admin/envs',         function () use ($app) { call_user_func($app->adminEnvironmentsPage);        });
$app->post('/admin/envs',         function () use ($app) { call_user_func($app->adminEnvironmentsHandlerPage); });
$app->get ('/admin/repositories', function () use ($app) { call_user_func($app->adminRepositoriesPage);        });
$app->post('/admin/repositories', function () use ($app) { call_user_func($app->adminRepositoriesHandlerPage); });
$app->get ('/admin/servers',      function () use ($app) { call_user_func($app->adminServersPage);             });
$app->post('/admin/servers',      function () use ($app) { call_user_func($app->adminServersHandlerPage);      });
$app->get ('/admin/deployments',  function () use ($app) { call_user_func($app->adminDeploymentsPage);         });
$app->post('/admin/deployments',  function () use ($app) { call_user_func($app->adminDeploymentsHandlerPage);  });
$app->get ('/admin/arrangements', function () use ($app) { call_user_func($app->adminArrangementsPage);        });
$app->post('/admin/arrangements', function () use ($app) { call_user_func($app->adminArrangementsHandlerPage); });
$app->get ('/admin/users',        function () use ($app) { call_user_func($app->adminUsersPage);               });

// GO FORTH AND PROSPER
$app->run();
