<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Hal\Core\Crypto\CryptoFilesystemFactory;
use Hal\Core\Crypto\Encryption;
use Hal\UI\Security\Auth;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\CSRFManager;
use Hal\UI\Security\UserAuthentication\GitHubAuth;
use Hal\UI\Security\UserAuthentication\GitHubEnterpriseAuth;
use Hal\UI\Security\UserAuthentication\InternalAuth;
use Hal\UI\Security\UserAuthentication\LDAPAuth;
use Hal\UI\Security\UserAuthentication\OAuthCallbackFactory;
use Hal\UI\Security\UserSessionHandler;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ->set('logger.error_handling.logging_levels', [
            'error' => 'critical'
        ])
    ;

    $s
        ->set(Auth::class)
            ->arg('$adapters', [
                'internal' =>   ref(InternalAuth::class),
                'ldap' =>       ref(LDAPAuth::class),
                'gh' =>         ref(GitHubAuth::class),
                'ghe' =>        ref(GitHubEnterpriseAuth::class)
            ])

        // Auth methods
        ->set(InternalAuth::class)
            ->arg('$em', ref(EntityManagerInterface::class))

        ->set(LDAPAuth::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$queryRestriction', '%ldap.query_restriction%')
            ->arg('$defaultUsernameAttribute', '%ldap.unique_attribute%')

        ->set(GitHubAuth::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$guzzle', ref('auth.github.http_client'))
            ->arg('$callbackFactory', ref(OAuthCallbackFactory::class))
            ->arg('$requiredScopes', '%github_auth.required_scopes%')

        ->set(GitHubEnterpriseAuth::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$guzzle', ref('auth.github.http_client'))
            ->arg('$callbackFactory', ref(OAuthCallbackFactory::class))
            ->arg('$requiredScopes', '%github_auth.required_scopes%')
    ;

    $s
        // Helpers
        ->set('auth.github.http_client', Client::class)

        ->set(OAuthCallbackFactory::class)
            ->arg('$baseRequest', ref('request'))
            ->arg('$uri', ref('uri'))
            ->arg('$routeName', '%github_auth.callback_route_name%')
    ;

    $s
        // Authorizations
        ->set(AuthorizationService::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$json', ref('json'))
            ->call('setCache', [ref('cache')])
            ->call('setCacheTTL', ['%cache.permissions.ttl%'])

        ->set(AuthorizationHydrator::class)
            ->arg('$em', ref(EntityManagerInterface::class))
    ;

    $s
        // Forms
        ->set(CSRFManager::class)
            ->arg('$clock', ref('clock'))

        ->set(UserSessionHandler::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$authorizationService', ref(AuthorizationService::class))
            ->arg('$clock', ref('clock'))
    ;

    // Encryption
    $s
        ->set(Encryption::class)
            ->factory([ref(CryptoFilesystemFactory::class), 'getCrypto'])
            ->lazy()

        ->set(CryptoFilesystemFactory::class)
            ->arg('$keyPath', '%encryption.secret_path%')
    ;
};
