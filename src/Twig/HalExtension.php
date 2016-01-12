<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Twig;

use QL\Panthor\Http\EncryptedCookies;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Service\GlobalMessageService;
use QL\Hal\Session;
use QL\Hal\Utility\NameFormatter;
use QL\Hal\Utility\TimeFormatter;
use Slim\Http\Request;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type EncryptedCookies
     */
    private $cookies;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type GlobalMessageService
     */
    private $messageService;

    /**
     * @type TimeFormatter
     */
    private $time;

    /**
     * @type NameFormatter
     */
    private $name;

    /**
     * @type array|null
     */
    private $parsedNavigationList;

    /**
     * @param Request $request
     * @param EncryptedCookies $cookies
     * @param Session $session
     * @param GlobalMessageService $messageService
     * @param TimeFormatter $time
     * @param NameFormatter $name
     */
    public function __construct(
        Request $request,
        EncryptedCookies $cookies,
        Session $session,
        GlobalMessageService $messageService,
        TimeFormatter $time,
        NameFormatter $name
    ) {
        $this->request = $request;
        $this->cookies = $cookies;
        $this->session = $session;
        $this->messageService = $messageService;
        $this->time = $time;
        $this->name = $name;
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
            new Twig_SimpleFunction('isSeriousBusinessMode', [$this, 'isSeriousBusinessMode']),
            new Twig_SimpleFunction('hash', [$this, 'hash']),
            new Twig_SimpleFunction('html5duration', [$this->time, 'html5duration'], ['is_safe' => ['html']]),

            // name
            new Twig_SimpleFunction('getUsersName', [$this->name, 'getUsersName']),
            new Twig_SimpleFunction('getUsersFirstName', [$this->name, 'getUsersFirstName']),
            new Twig_SimpleFunction('getUsersActualName', [$this->name, 'getUsersActualName']),
            new Twig_SimpleFunction('getUsersFreudianName', [$this->name, 'getUsersFreudianName']),
            new Twig_SimpleFunction('getAvatarLink', [$this, 'getAvatarLink']),

            // session
            new Twig_SimpleFunction('session_flash', [$this->session, 'flash']),
            new Twig_SimpleFunction('session_get', [$this->session, 'get']),

            // services
            new Twig_SimpleFunction('globalMessage', [$this->messageService, 'load']),
            new Twig_SimpleFunction('isUpdateTickOn', [$this->messageService, 'isUpdateTickOn']),

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

            new Twig_SimpleFilter('jsonPretty', [$this, 'jsonPretty']),

            new Twig_SimpleFilter('formatBuildId', [$this, 'formatBuildId']),
            new Twig_SimpleFilter('formatPushId', [$this, 'formatPushId']),
            new Twig_SimpleFilter('formatEvent', [$this, 'formatEvent']),
            new Twig_SimpleFilter('sanitizeToString', [$this, 'sanitizeToString']),
            new Twig_SimpleFilter('sliceString', [$this, 'sliceString']),
            new Twig_SimpleFilter('displayUrl', [$this, 'formatUrlForDisplay']),

            new Twig_SimpleFilter('formatDeploymentDetailsLabel', [$this, 'formatDeploymentDetailsLabel']),
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
            new Twig_SimpleTest('build', function ($entity) { return $entity instanceof Build; }),
            new Twig_SimpleTest('push', function ($entity) { return $entity instanceof Push; })
        ];
    }

    /**
     * Attempt to pretty print JSON string
     *
     * @param $json
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
     * @param string $value
     * @param int $size
     * @return string
     */
    public function sliceString($value, $size = 20)
    {
        $len = mb_strlen($value);
        if ($len <= $size + 3) {
            return $value;
        } else {
            return substr($value, 0, $size) . '...';
        }
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function sanitizeToString($data)
    {
        // bool
        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        // scalar
        if (is_scalar($data)) {
            return (string) $data;
        }

        // stringable
        if (is_object($data) && method_exists($data, '__toString')) {
            return (string) $data;
        }

        // array
        if (is_array($data)) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        // object
        return get_class($data);
    }

    /**
     * Get a simple hash for a provided value.
     *
     * @param mixed $data
     * @return string
     */
    public function hash($data)
    {
        return md5($data);
    }

    /**
     * @return boolean
     */
    public function isSeriousBusinessMode()
    {
        $cookie = $this->cookies->getCookie('seriousbusiness');

        return (bool) $cookie;
    }

    /**
     * Format a URL string for display
     *
     * @param string $url
     * @return string
     */
    public function formatUrlForDisplay($url)
    {
        if (!is_string($url)) {
            return $url;
        }

        // pretty dumb atm
        return rtrim(preg_replace('#^http[s]?://#', '', $url), '/');
    }

    /**
     * Format a deployment details label
     *
     * @param Deployment|null $deployment
     *
     * @return string
     */
    public function formatDeploymentDetailsLabel(Deployment $deployment = null)
    {
        if (!$deployment) {
            return 'Path';
        }

        $type = $deployment->server()->type();

        if ($type === ServerEnum::TYPE_EB) {
            return 'EB Environment';

        } elseif ($type === ServerEnum::TYPE_EC2) {
            return 'EC2 Pool';

        } elseif ($type === ServerEnum::TYPE_S3) {
            return 'S3 Bucket';

        } elseif ($type === ServerEnum::TYPE_CD) {
            return 'CodeDeploy Group';
        }

        return 'Path';
    }

    /**
     * @param string $email
     * @param int $size
     *
     * @return string
     */
    public function getAvatarLink($email, $size = 100)
    {
        $isHttpsOn = ($this->request->getScheme() === 'https');

        $email = strtolower(trim($email));

        $default = sprintf('%s://skluck.github.io/hal/halprofile_100.jpg', $isHttpsOn ? 'https' : 'http');

        return sprintf(
            '%s://www.gravatar.com/avatar/%s?s=%d&d=%s',
            $isHttpsOn ? 'https' : 'http',
            md5($email),
            $size,
            urlencode($default)
        );

    }
}
