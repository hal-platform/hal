<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Aws\Credentials\CredentialProvider as AwsCredentialProvider;
use Aws\Sdk;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\AWS\AWSAuthenticator;
use Hal\Core\AWS\CredentialProvider;
use Hal\Core\Crypto\Encryption;
use Psr\Log\LoggerInterface;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('aws.sdk_version', 'latest')
    ;

    $s
        (Sdk::class)
            ->arg('$args', [
                'version' => '%aws.sdk_version%'
            ])

        (AwsCredentialProvider::class)
            ->factory([AwsCredentialProvider::class, 'ini'])
            ->arg('$profile', null)
            ->arg('$filename', '%aws.host_credentials_path%')

        (AWSAuthenticator::class)
            ->arg('$logger', ref(LoggerInterface::class))
            ->arg('$provider', ref(CredentialProvider::class))
            ->arg('$aws', ref(Sdk::class))

        (CredentialProvider::class)
            ->arg('$logger', ref(LoggerInterface::class))
            ->arg('$encryption', ref(Encryption::class))
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$aws', ref(Sdk::class))
            ->arg('$useHostCredentials', '%aws.use_host_credentials%')
            ->call('$setHostCredentials', [ref(AwsCredentialProvider::class)])
    ;
};
