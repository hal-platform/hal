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
        // ('validator_types.target', [
        //     'cd' => CodeDeployValidator::class,
        //     'eb' => ElasticBeanstalkValidator::class,
        //     'rsync' => RSyncValidator::class,
        //     's3' => S3Validator::class,
        //     'script' => ScriptValidator::class,
        // ])
        // ('validator_types.target_template', [
        //     'cd' => 'validator.target_tempate.cd',
        //     'eb' => 'validator.target_tempate.eb',
        //     'rsync' => 'validator.target_tempate.rsync',
        //     's3' => 'validator.target_tempate.s3',
        //     'script' => 'validator.target_tempate.script',
        // ])
        // ('validator_types.vcs', [
        //     'ghe' => GitHubEnterpriseValidator::class,
        // ])
        // ('validator_types.idp', [
        //     'gh' => IDPGitHubValidator::class,
        //     'ghe' => IDPGitHubEnterpriseValidator::class,
        //     'ldap' => LDAPValidator::class,
        //     'internal' => InternalValidator::class,
        // ])
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
            ->call('addTypeValidator', ['cd', ref(CodeDeployValidator::class)])
            ->call('addTypeValidator', ['eb', ref(ElasticBeanstalkValidator::class)])
            ->call('addTypeValidator', ['rsync', ref(RSyncValidator::class)])
            ->call('addTypeValidator', ['s3', ref(S3Validator::class)])
            ->call('addTypeValidator', ['script', ref(ScriptValidator::class)])
        (CodeDeployValidator::class)
        (ElasticBeanstalkValidator::class)
        (RSyncValidator::class)
        (S3Validator::class)
        (ScriptValidator::class)
    ;

    $s
        (TargetTemplateValidator::class)
            ->call('addTypeValidator', ['cd', ref('validator.target_template.cd')])
            ->call('addTypeValidator', ['eb', ref('validator.target_template.eb')])
            ->call('addTypeValidator', ['rsync', ref('validator.target_template.rsync')])
            ->call('addTypeValidator', ['s3', ref('validator.target_template.s3')])
            ->call('addTypeValidator', ['script', ref('validator.target_template.script')])
        ('validator.target_template.cd', CodeDeployValidator::class)
            ->arg('$s3validator', ref('validator.target_template.s3'))
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_template.eb', ElasticBeanstalkValidator::class)
            ->arg('$s3validator', ref('validator.target_template.s3'))
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_template.rsync', RSyncValidator::class)
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_template.s3', S3Validator::class)
            ->call('withFlag', ['ALLOW_OPTIONAL'])
        ('validator.target_template.script', ScriptValidator::class)
            ->call('withFlag', ['ALLOW_OPTIONAL'])
    ;

    $s
        (VersionControlProviderValidator::class)
            ->call('addTypeValidator', ['ghe', ref(GitHubEnterpriseValidator::class)])
        (GitHubEnterpriseValidator::class)
    ;

    $s
        (UserIdentityProviderValidator::class)
            ->call('addTypeValidator', ['gh', ref(IDPGitHubValidator::class)])
            ->call('addTypeValidator', ['ghe', ref(IDPGitHubEnterpriseValidator::class)])
            ->call('addTypeValidator', ['ldap', ref(LDAPValidator::class)])
            ->call('addTypeValidator', ['internal', ref(InternalValidator::class)])
        (IDPGitHubValidator::class)
        (IDPGitHubEnterpriseValidator::class)
        (LDAPValidator::class)
        (InternalValidator::class)
    ;
};
