<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Hal\UI\Utility\TimeFormatter;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\CredentialEnum;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\Core\Type\VCSProviderEnum;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use Twig\Extension\AbstractExtension;

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
    public function __construct(
        TimeFormatter $time,
        $gravatarFallbackImageURL
    ) {
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

            new TwigFilter('jsonPretty', [$this, 'jsonPretty'], ['is_safe' => ['html']]),

            new TwigFilter('formatBuildId', [$this, 'formatBuildId']),
            new TwigFilter('formatPushId', [$this, 'formatPushId']),
            new TwigFilter('formatEvent', [$this, 'formatEvent']),

            new TwigFilter('shortGUID', [$this, 'shortGUID']),
            new TwigFilter('short_guid', [$this, 'shortGUID']),

            new TwigFilter('occurences', function($haystack, $needle) {
                if (!is_string($haystack) || !is_string($needle)) return 0;

                return substr_count($haystack, $needle);
            }),

            // @todo move these to entities?
            new TwigFilter('idp_type', [$this, 'formatIDP']),
            new TwigFilter('vcs_type', [$this, 'formatVCS']),
            new TwigFilter('credential_type', [$this, 'formatCredential']),
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
            })
        ];
    }

    /**
     * Attempt to pretty print JSON string
     *
     * @param string $json
     *
     * @return string
     */
    public function jsonPretty($json)
    {
        $raw = json_decode($json, true);

        // bail on badly formatted json
        if ($raw === null) {
            return $json;
        }

        return json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string|int $id
     *
     * @return string
     */
    public function formatBuildId($id)
    {
        if (preg_match('#^b[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$#', $id)) {
            return strtolower(substr($id, 6));
        }

        return substr($id, 0, 10);
    }

    /**
     * @param string|int $id
     *
     * @return string
     */
    public function formatPushId($id)
    {
        if (preg_match('#^p[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$#', $id)) {
            return strtolower(substr($id, 6));
        }

        return substr($id, 0, 10);
    }

    /**
     * @param string $event
     *
     * @return string
     */
    public function formatEvent($event)
    {
        if (preg_match('#^(build|push).([a-z]*)$#', $event, $matches)) {
            $subevent = array_pop($matches);
            return ucfirst($subevent);
        }

        return $event;
    }

    /**
     * @param string $entity
     *
     * @return string
     */
    public function shortGUID($entity)
    {
        if (is_object($entity) && is_callable([$entity, 'id'])) {
            $entity = $entity->id();
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

        switch ($provider) {
            case IdentityProviderEnum::TYPE_INTERNAL:
                return 'Internal';

            case IdentityProviderEnum::TYPE_LDAP:
                return 'LDAP';

            case IdentityProviderEnum::TYPE_GITHUB:
                return 'GitHub.com';

            case IdentityProviderEnum::TYPE_GITHUB_ENTERPRISE:
                return 'GitHub Ent.';

            default:
                return 'Unknown';
        }
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

        switch ($provider) {
            case VCSProviderEnum::TYPE_GIT:
                return 'Git';

            case VCSProviderEnum::TYPE_GITHUB:
                return 'GitHub.com';

            case VCSProviderEnum::TYPE_GITHUB_ENTERPRISE:
                return 'GitHub Ent.';

            default:
                return 'Unknown';
        }
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

        switch ($credential) {
            case CredentialEnum::TYPE_AWS_ROLE:
                return 'AWS STS Role';

            case CredentialEnum::TYPE_AWS_STATIC:
                return 'AWS Static Token';

            case CredentialEnum::TYPE_PRIVATEKEY:
                return 'Private Key';

            default:
                return 'Unknown';
        }
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
}
