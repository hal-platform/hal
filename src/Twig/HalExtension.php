<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Hal\UI\Utility\TimeFormatter;
// use Hal\Core\Entity\Build;
// use Hal\Core\Entity\Release;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\CredentialEnum;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\Core\Type\VCSProviderEnum;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

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
     * Get the extension name
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
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
            new Twig_SimpleFunction('html5duration', [$this->time, 'html5duration'], ['is_safe' => ['html']]),
            new Twig_SimpleFunction('hash', [$this, 'hash']),

            // user
            new Twig_SimpleFunction('getAvatarLink', [$this, 'getAvatarLink']),
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
            new Twig_SimpleFilter('reldate', [$this->time, 'relative']),
            new Twig_SimpleFilter('html5date', [$this->time, 'html5'], ['is_safe' => ['html']]),

            new Twig_SimpleFilter('jsonPretty', [$this, 'jsonPretty'], ['is_safe' => ['html']]),

            new Twig_SimpleFilter('formatBuildId', [$this, 'formatBuildId']),
            new Twig_SimpleFilter('formatPushId', [$this, 'formatPushId']),
            new Twig_SimpleFilter('formatEvent', [$this, 'formatEvent']),

            new Twig_SimpleFilter('shortGUID', [$this, 'shortGUID']),
            new Twig_SimpleFilter('short_guid', [$this, 'shortGUID']),

            // @todo move these to entities?
            new Twig_SimpleFilter('idp_type', [$this, 'formatIDP']),
            new Twig_SimpleFilter('vcs_type', [$this, 'formatVCS']),
            new Twig_SimpleFilter('credential_type', [$this, 'formatCredential']),
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
            new Twig_SimpleTest('build', function ($entity) {
                return $entity instanceof Build;
            }),
            new Twig_SimpleTest('release', function ($entity) {
                return $entity instanceof Release;
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
            $provider = $provider->type();
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
            $provider = $provider->type();
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
            $credential = $credential->type();
        }

        switch ($credential) {
            case CredentialEnum::TYPE_AWS_STATIC:
                return 'AWS Static Token';
            case CredentialEnum::TYPE_AWS_ROLE:
                return 'AWS STS Role';
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
    public function getAvatarLink($email, $size = 100)
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
