<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Hal\UI\Service\GlobalMessageService;
use Hal\UI\Utility\NameFormatter;
use Hal\UI\Utility\TimeFormatter;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    /**
     * @var GlobalMessageService
     */
    private $messageService;

    /**
     * @var TimeFormatter
     */
    private $time;

    /**
     * @var NameFormatter
     */
    private $name;

    /**
     * @var string
     */
    private $gravatarFallbackImageURL;

    /**
     * @param GlobalMessageService $messageService
     * @param TimeFormatter $time
     * @param NameFormatter $name
     * @param string $gravatarFallbackImageURL
     */
    public function __construct(
        GlobalMessageService $messageService,
        TimeFormatter $time,
        NameFormatter $name,
        $gravatarFallbackImageURL
    ) {
        $this->messageService = $messageService;
        $this->time = $time;
        $this->name = $name;

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

            // name
            new Twig_SimpleFunction('getUsersName', [$this->name, 'getUsersName']),
            new Twig_SimpleFunction('getUsersFirstName', [$this->name, 'getUsersFirstName']),
            new Twig_SimpleFunction('getUsersActualName', [$this->name, 'getUsersActualName']),
            new Twig_SimpleFunction('getUsersFreudianName', [$this->name, 'getUsersFreudianName']),
            new Twig_SimpleFunction('getAvatarLink', [$this, 'getAvatarLink']),

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
            new Twig_SimpleFilter('sliceString', [$this, 'sliceString']),

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
     * @param string $value
     * @param int $size
     *
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

        } elseif ($type === ServerEnum::TYPE_S3) {
            return 'S3 Bucket';

        } elseif ($type === ServerEnum::TYPE_CD) {
            return 'CodeDeploy Group';

        } elseif ($type === ServerEnum::TYPE_SCRIPT) {
            return 'Context';
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
