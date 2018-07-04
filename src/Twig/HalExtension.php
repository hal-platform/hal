<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Job\JobEvent;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\CredentialEnum;
use Hal\Core\Type\JobEventStageEnum;
use Hal\Core\Type\TargetEnum;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Utility\TimeFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class HalExtension extends AbstractExtension
{
    /**
     * @var TimeFormatter
     */
    private $time;

    /**
     * @var string
     */
    private $gravatarFallbackImageURL;

    /**
     * @param TimeFormatter $time
     * @param string $gravatarFallbackImageURL
     */
    public function __construct(TimeFormatter $time, string $gravatarFallbackImageURL)
    {
        $this->time = $time;
        $this->gravatarFallbackImageURL = $gravatarFallbackImageURL;
    }

    /**
     * Get an array of Twig Functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            // util
            new TwigFunction('html5duration', [$this->time, 'html5duration'], ['is_safe' => ['html']]),
            new TwigFunction('hash', [$this, 'hash']),

            // user
            new TwigFunction('get_gravatar_link', [$this, 'getGravatarLink']),
        ];
    }

    /**
     * Get an array of Twig Filters
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('reldate', [$this->time, 'relative']),
            new TwigFilter('html5date', [$this->time, 'html5'], ['is_safe' => ['html']]),

            new TwigFilter('json_pretty', [$this, 'formatPrettyJSON'], ['is_safe' => ['html']]),

            new TwigFilter('short_guid', [$this, 'formatShortGUID']),
            new TwigFilter('occurences', [$this, 'findOccurences']),

            // @todo move these to entities?
            new TwigFilter('idp_type', [$this, 'formatIDP']),
            new TwigFilter('vcs_type', [$this, 'formatVCS']),
            new TwigFilter('credential_type', [$this, 'formatCredential']),
            new TwigFilter('target_type', [$this, 'formatTarget']),
            new TwigFilter('event_stage', [$this, 'formatEventStage']),
        ];
    }

    /**
     * Get an array of Twig Tests
     *
     * @return array
     */
    public function getTests()
    {
        return [
            new TwigTest('build', function ($entity) {
                return $entity instanceof Build;
            }),
            new TwigTest('release', function ($entity) {
                return $entity instanceof Release;
            }),
            new TwigTest('organization', function ($entity) {
                return $entity instanceof Organization;
            }),
        ];
    }


    /**
     * @param mixed $needle
     * @param mixed $haystack
     *
     * @return int
     */
    public function findOccurences($needle, $haystack)
    {
        if (!is_string($haystack) || !is_string($needle)) {
            return 0;
        }

        return substr_count($haystack, $needle);
    }

    /**
     * Attempt to pretty print JSON string
     *
     * @param string $json
     *
     * @return string
     */
    public function formatPrettyJSON($json)
    {
        $raw = json_decode($json, true);

        // bail on badly formatted json
        if ($raw === null) {
            return $json;
        }

        return json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $event
     *
     * @return string
     */
    public function formatEventStage($event)
    {
        if ($event instanceof JobEvent) {
            $event = $event->stage();
        }

        return JobEventStageEnum::format($event);
    }

    /**
     * @param mixed $entity
     *
     * @return string
     */
    public function formatShortGUID($entity)
    {
        if (is_object($entity) && is_callable([$entity, 'id'])) {
            $entity = call_user_func([$entity, 'id']);
        }

        return substr($entity, 0, 8);
    }

    /**
     * @param mixed $provider
     *
     * @return string
     */
    public function formatIDP($provider)
    {
        if ($provider instanceof UserIdentityProvider) {
            return $provider->formatType();
        }

        return IdentityProviderEnum::format($provider);
    }

    /**
     * @param mixed $provider
     *
     * @return string
     */
    public function formatVCS($provider)
    {
        if ($provider instanceof VersionControlProvider) {
            return $provider->formatType();
        }

        return VCSProviderEnum::format($provider);
    }

    /**
     * @param mixed $credential
     *
     * @return string
     */
    public function formatCredential($credential)
    {
        if ($credential instanceof Credential) {
            return $credential->formatType();
        }

        return CredentialEnum::format($credential);
    }

    /**
     * @param mixed $target
     *
     * @return string
     */
    public function formatTarget($target)
    {
        if ($target instanceof Target) {
            return $target->formatType();
        }

        return TargetEnum::format($target);
    }

    /**
     * @param string $email
     * @param int $size
     *
     * @return string
     */
    public function getGravatarLink($email, $size = 100)
    {
        $email = strtolower(trim($email));
        // $scheme = ($this->request->getScheme() === 'https') ? 'https' : 'http';
        $scheme = 'http';
        $default = sprintf('%s://%s', $scheme, $this->gravatarFallbackImageURL);

        return sprintf(
            '%s://secure.gravatar.com/avatar/%s?s=%d&d=%s',
            $scheme,
            md5($email),
            $size,
            urlencode($default)
        );
    }

    /**
     * Get a simple hash for a provided value.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function hash($data)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        return md5($data);
    }
}
