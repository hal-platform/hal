<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Validator\ApplicationValidator;
use Hal\UI\Validator\BuildValidator;
use Hal\UI\Validator\CredentialValidator;
use Hal\UI\Validator\EncryptedPropertyValidator;
use Hal\UI\Validator\EnvironmentValidator;
use Hal\UI\Validator\IdentityProviders\GitHubEnterpriseValidator as IDPGitHubEnterpriseValidator;
use Hal\UI\Validator\IdentityProviders\GitHubValidator as IDPGitHubValidator;
use Hal\UI\Validator\IdentityProviders\InternalValidator;
use Hal\UI\Validator\IdentityProviders\LDAPValidator;
use Hal\UI\Validator\MetaValidator;
use Hal\UI\Validator\OrganizationValidator;
use Hal\UI\Validator\PermissionsValidator;
use Hal\UI\Validator\ReleaseValidator;
use Hal\UI\Validator\Targets\CodeDeployValidator;
use Hal\UI\Validator\Targets\ElasticBeanstalkValidator;
use Hal\UI\Validator\Targets\RSyncValidator;
use Hal\UI\Validator\Targets\S3Validator;
use Hal\UI\Validator\Targets\ScriptValidator;
use Hal\UI\Validator\TargetTemplateValidator;
use Hal\UI\Validator\TargetValidator;
use Hal\UI\Validator\UserIdentityProviderValidator;
use Hal\UI\Validator\UserIdentityValidator;
use Hal\UI\Validator\UserValidator;
use Hal\UI\Validator\VersionControl\GitHubEnterpriseValidator;
use Hal\UI\Validator\VersionControlProviderValidator;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('validator_types.target', [
            'cd' => ref(CodeDeployValidator::class),
            'eb' => ref(ElasticBeanstalkValidator::class),
            'rsync' => ref(RSyncValidator::class),
            's3' => ref(S3Validator::class),
            'script' => ref(ScriptValidator::class),
        ])
        ('validator_types.target_template', [
            'cd' => ref('validator.target_tempate.cd'),
            'eb' => ref('validator.target_tempate.eb'),
            'rsync' => ref('validator.target_tempate.rsync'),
            's3' => ref('validator.target_tempate.s3'),
            'script' => ref('validator.target_tempate.script'),
        ])
        ('validator_types.vcs', [
            'ghe' => ref(GitHubEnterpriseValidator::class),
        ])
        ('validator_types.idp', [
            'gh' => ref(IDPGitHubValidator::class),
            'ghe' => ref(IDPGitHubEnterpriseValidator::class),
            'ldap' => ref(LDAPValidator::class),
            'internal' => ref(InternalValidator::class),
        ])
    ;

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        (OrganizationValidator::class)
        (EnvironmentValidator::class)
        (ApplicationValidator::class)
        (CredentialValidator::class)
        (EncryptedPropertyValidator::class)
        (PermissionsValidator::class)
        (BuildValidator::class)
        (ReleaseValidator::class)
        (MetaValidator::class)
        (UserValidator::class)
        (UserIdentityValidator::class)
    ;

    $s
        (TargetValidator::class)
            ->arg('$typeValidators', '%validator_types.target%')
        (CodeDeployValidator::class)
        (ElasticBeanstalkValidator::class)
        (RSyncValidator::class)
        (S3Validator::class)
        (ScriptValidator::class)
    ;

    $s
        (TargetTemplateValidator::class)
            ->arg('$typeValidators', '%validator_types.target_template%')
        ('validator.target_tempate.cd', CodeDeployValidator::class)
            ->arg('$s3validator', ref('validator.target_tempate.s3'))
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_tempate.eb', ElasticBeanstalkValidator::class)
            ->arg('$s3validator', ref('validator.target_tempate.s3'))
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_tempate.rsync', RSyncValidator::class)
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_tempate.s3', S3Validator::class)
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_tempate.rsync', ScriptValidator::class)
            ->call('withFlag', ['ALLOW_OPTIONAL'])
    ;

    $s
        (VersionControlProviderValidator::class)
            ->arg('$typeValidators', '%validator_types.vcs%')
        (GitHubEnterpriseValidator::class)
    ;

    $s
        (UserIdentityProviderValidator::class)
            ->arg('$typeValidators', '%validator_types.idp%')
        (IDPGitHubValidator::class)
        (IDPGitHubEnterpriseValidator::class)
        (LDAPValidator::class)
        (InternalValidator::class)
    ;
};
