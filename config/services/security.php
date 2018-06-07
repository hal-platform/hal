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
use QL\MCP\Common\Clock;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('logger.error_handling.logging_levels', [
            'error' => 'critical'
        ])
    ;

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        (Auth::class)
            ->arg('$adapters', [
                'internal' =>   ref(InternalAuth::class),
                'ldap' =>       ref(LDAPAuth::class),
                'gh' =>         ref(GitHubAuth::class),
                'ghe' =>        ref(GitHubEnterpriseAuth::class)
            ])

        // Auth methods
        (InternalAuth::class)

        (LDAPAuth::class)
            ->arg('$queryRestriction', '%ldap.query_restriction%')
            ->arg('$defaultUsernameAttribute', '%ldap.unique_attribute%')

        (GitHubAuth::class)
            ->arg('$guzzle', ref('auth.github.http_client'))
            ->arg('$requiredScopes', '%github_auth.required_scopes%')

        (GitHubEnterpriseAuth::class)
            ->arg('$guzzle', ref('auth.github.http_client'))
            ->arg('$requiredScopes', '%github_auth.required_scopes%')
    ;

    $s
        // Helpers
        ('auth.github.http_client', Client::class)

        (OAuthCallbackFactory::class)
            ->arg('$baseRequest', ref('request'))
            ->arg('$routeName', '%github_auth.callback_route_name%')
    ;

    $s
        // Authorizations
        (AuthorizationService::class)
            ->call('setCache', [ref('cache')])
            ->call('setCacheTTL', ['%cache.permissions.ttl%'])

        (AuthorizationHydrator::class)
    ;

    $s
        // Forms
        (CSRFManager::class)
        (UserSessionHandler::class)
    ;

    // Encryption
    $s
        (Encryption::class)
            ->factory([ref(CryptoFilesystemFactory::class), 'getCrypto'])
            ->lazy()

        (CryptoFilesystemFactory::class)
            ->arg('$keyPath', '%encryption.secret_path%')
    ;
};
