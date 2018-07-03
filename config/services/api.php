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

    $s
        ('api.cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%cache.type.api%')

        (ResponseFormatter::class)
            ->call('setCache', [ref('api.cache')])
            ->call('setCacheTimes', ['%api.cachetimes%'])
            ->autowire()

        (APIRateLimiter::class)
            ->arg('$rateLimitTimes', '%api.ratelimits%')
            ->autowire()

        (NormalizerInterface::class, Normalizer::class)
            ->arg('$normalizers', [
                Application::class => ref(ApplicationNormalizer::class),
                Build::class => ref(BuildNormalizer::class),
                Environment::class => ref(EnvironmentNormalizer::class),
                Hyperlink::class => ref(HyperlinkNormalizer::class),
                JobEvent::class => ref(EventNormalizer::class),
                Organization::class => ref(OrganizationNormalizer::class),
                Release::class => ref(ReleaseNormalizer::class),
                Target::class => ref(TargetNormalizer::class),
                TargetTemplate::class => ref(TemplateNormalizer::class),
                TimePoint::class => ref(TimePointNormalizer::class),
                User::class => ref(UserNormalizer::class),
                VersionControlProvider::class => ref(VersionControlProviderNormalizer::class),
            ])
    ;

    $s = $container->services();

    $s
        ->defaults()
            ->autowire()

        (ApplicationNormalizer::class)
        (EnvironmentNormalizer::class)
        (HyperlinkNormalizer::class)
            ->arg('$baseRequest', ref('request'))
        (OrganizationNormalizer::class)
        (TargetNormalizer::class)
        (TemplateNormalizer::class)
        (TimePointNormalizer::class)
        (UserNormalizer::class)
        (BuildNormalizer::class)
        (EventNormalizer::class)
        (ReleaseNormalizer::class)
        (VersionControlProviderNormalizer::class)
    ;
};
