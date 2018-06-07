<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Job\JobEvent;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Entity\User;
use Hal\UI\API\APIRateLimiter;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\Normalizer;
use Hal\UI\API\Normalizer\ApplicationNormalizer;
use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\Normalizer\EnvironmentNormalizer;
use Hal\UI\API\Normalizer\EventNormalizer;
use Hal\UI\API\Normalizer\HyperlinkNormalizer;
use Hal\UI\API\Normalizer\OrganizationNormalizer;
use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\Normalizer\TargetNormalizer;
use Hal\UI\API\Normalizer\TemplateNormalizer;
use Hal\UI\API\Normalizer\TimePointNormalizer;
use Hal\UI\API\Normalizer\UserNormalizer;
use Hal\UI\API\Normalizer\VersionControlProviderNormalizer;
use Hal\UI\API\NormalizerInterface;
use Hal\UI\API\ResponseFormatter;
use Psr\SimpleCache\CacheInterface;
use QL\MCP\Common\Time\TimePoint;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ->set('api.normalizers', [
            Application::class => ref(ApplicationNormalizer::class),
            Environment::class => ref(EnvironmentNormalizer::class),
            Hyperlink::class => ref(HyperlinkNormalizer::class),
            Organization::class => ref(OrganizationNormalizer::class),
            Target::class => ref(TargetNormalizer::class),
            TargetTemplate::class => ref(TemplateNormalizer::class),
            TimePoint::class => ref(TimePointNormalizer::class),
            User::class => ref(UserNormalizer::class),

            Build::class => ref(BuildNormalizer::class),
            JobEvent::class => ref(EventNormalizer::class),
            Release::class => ref(ReleaseNormalizer::class),
            VersionControlProvider::class => ref(VersionControlProviderNormalizer::class),
        ])
    ;

    $s
        ->set('api.cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%cache.type.api%')

        ->set(ResponseFormatter::class)
            ->autowire()
            ->call('setCache', [ref('api.cache')])
            ->call('setCacheTimes', ['%api.cachetimes%'])

        ->set(APIRateLimiter::class)
            ->arg('$rateLimitTimes', '%api.ratelimits%')
            ->autowire()
    ;

    $s
        ->set(NormalizerInterface::class, Normalizer::class)
            ->arg('$normalizers', '%api.normalizers%')

        ->set(ApplicationNormalizer::class)
            ->autowire()
        ->set(EnvironmentNormalizer::class)
            ->autowire()
        ->set(HyperlinkNormalizer::class)
            ->arg('$baseRequest', ref('request'))
            ->autowire()
        ->set(OrganizationNormalizer::class)
            ->autowire()
        ->set(TargetNormalizer::class)
            ->autowire()
        ->set(TemplateNormalizer::class)
            ->autowire()
        ->set(TimePointNormalizer::class)
            ->autowire()
        ->set(UserNormalizer::class)
            ->autowire()
        ->set(BuildNormalizer::class)
            ->autowire()
        ->set(EventNormalizer::class)
            ->autowire()
        ->set(ReleaseNormalizer::class)
            ->autowire()
        ->set(VersionControlProviderNormalizer::class)
            ->autowire()
    ;
};
