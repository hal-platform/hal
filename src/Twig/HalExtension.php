<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Panthor\Http\EncryptedCookies;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\NameHelper;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GlobalMessageService;
use QL\Hal\Utility\TimeFormatter;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    /**
     * @type EncryptedCookies
     */
    private $cookies;

    /**
     * @type GlobalMessageService
     */
    private $messageService;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type TimeFormatter
     */
    private $time;

    /**
     * @type NameHelper
     */
    private $name;

    /**
     * @type array|null
     */
    private $parsedNavigationList;

    /**
     * @param EncryptedCookies $cookies
     * @param GlobalMessageService $messageService
     * @param UrlHelper $url
     * @param TimeFormatter $time
     * @param NameHelper $name
     */
    public function __construct(
        EncryptedCookies $cookies,
        GlobalMessageService $messageService,
        UrlHelper $url,
        TimeFormatter $time,
        NameHelper $name
    ) {
        $this->cookies = $cookies;
        $this->messageService = $messageService;
        $this->url = $url;
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
            new Twig_SimpleFunction('isNavOn', [$this, 'isNavigationOn']),
            new Twig_SimpleFunction('isSeriousBusinessMode', [$this, 'isSeriousBusinessMode']),
            new Twig_SimpleFunction('globalMessage', [$this->messageService, 'load']),
            new Twig_SimpleFunction('hash', [$this, 'hash']),

            new Twig_SimpleFunction('html5duration', [$this->time, 'html5duration'], ['is_safe' => ['html']]),

            // other
            new Twig_SimpleFunction('getUsersName', [$this->name, 'getUsersName']),
            new Twig_SimpleFunction('getUsersFirstName', [$this->name, 'getUsersFirstName']),
            new Twig_SimpleFunction('getUsersActualName', [$this->name, 'getUsersActualName']),
            new Twig_SimpleFunction('getUsersFreudianName', [$this->name, 'getUsersFreudianName'])
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

            new Twig_SimpleFilter('formatDeploymentServer', [$this, 'formatDeploymentServer']),
            new Twig_SimpleFilter('formatDeploymentDetails', [$this, 'formatDeploymentDetails']),
            new Twig_SimpleFilter('formatDeploymentDetailsLabel', [$this, 'formatDeploymentDetailsLabel']),
            new Twig_SimpleFilter('formatServer', [$this, 'formatServer']),
            new Twig_SimpleFilter('formatServerType', [$this, 'formatServerType']),
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
     * @param string $navSelection
     * @return boolean
     */
    public function isNavigationOn($navSelection)
    {
        $cookie = $this->cookies->getCookie('navpreferences');
        if ($cookie === null) {
            $this->parsedNavigationList = $this->defaultNavigation();
        } else {
            $this->parsedNavigationList = json_decode($cookie, true);
        }

        if (!array_key_exists($navSelection, $this->parsedNavigationList)) {
            return true;
        }

        return ($this->parsedNavigationList[$navSelection] === true);
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
     * Format a deployment server for display
     *
     * @param Deployment|null $deployment
     * @param bool $withDetails
     *
     * @return string
     */
    public function formatDeploymentServer(Deployment $deployment = null, $withDetails = false)
    {
        if (!$deployment) {
            return 'Unknown';
        }

        $type = $deployment->getServer()->getType();

        if ($withDetails) {
            if ($type === 'elasticbeanstalk') {
                return sprintf('EB (%s)', $deployment->getEbEnvironment());

            } elseif ($type === 'ec2') {
                return sprintf('EC2 (%s)', $deployment->getEc2Pool());
            }
        }

        return $this->formatServer($deployment->getServer());
    }

    /**
     * Format a deployment details for display
     *
     * @param Deployment|null $deployment
     *
     * @return string
     */
    public function formatDeploymentDetails(Deployment $deployment = null)
    {
        if (!$deployment) {
            return 'Unknown';
        }

        $type = $deployment->getServer()->getType();

        if ($type === 'elasticbeanstalk') {
            return $deployment->getEbEnvironment();

        } elseif ($type === 'ec2') {
            return $deployment->getEc2Pool();
        }

        return $deployment->getPath();
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

        $type = $deployment->getServer()->getType();

        if ($type === 'elasticbeanstalk') {
            return 'EB Environment';

        } elseif ($type === 'ec2') {
            return 'EC2 Pool';
        }

        return 'Path';
    }

    /**
     * Format a server for display
     *
     * @param Server|null $server
     *
     * @return string
     */
    public function formatServer(Server $server = null)
    {
        if (!$server) {
            return 'Unknown';
        }

        $type = $server->getType();

        if ($type === 'elasticbeanstalk') {
            return 'Elastic Beanstalk';

        } elseif ($type === 'ec2') {
            return 'EC2';
        }

        return $server->getName();
    }

    /**
     * Format a server type for display
     *
     * @param Server|null $server
     *
     * @return string
     */
    public function formatServerType(Server $server = null)
    {
        if (!$server) {
            return 'Unknown';
        }

        $type = $server->getType();

        $serverType = 'Internal (Rsync)';
        if ($type === 'elasticbeanstalk') {
            $serverType = 'Elastic Beanstalk';

        } elseif ($type === 'ec2') {
            $serverType = 'EC2 Autoscaling Pool';
        }

        return $serverType;
    }

    /**
     * @return array
     */
    private function defaultNavigation()
    {
        return [
            'dashboard' => true,
            'queue' => true,
            'repositories' => true,

            'servers' => false,
            'environments' => false,
            'users' => false,

            'admin' => true,
            'help' => true,
            'kraken' => true
        ];
    }
}
