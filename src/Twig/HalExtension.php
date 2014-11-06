<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User as DomainUser;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

/**
 *  Twig Extension for HAL9000
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HalExtension extends Twig_Extension
{
    const NAME = 'hal';

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type TimeHelper
     */
    private $time;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type string
     */
    private $applicationTitle;

    /**
     * @type string
     */
    private $applicationSha;

    /**
     * @param UrlHelper $url
     * @param TimeHelper $time
     * @param Session $session
     * @param string $appTitle
     * @param string $appSha
     */
    public function __construct(UrlHelper $url, TimeHelper $time, Session $session, $appTitle, $appSha)
    {
        $this->url = $url;
        $this->time = $time;
        $this->session = $session;

        $this->applicationTitle = $appTitle;
        $this->applicationSha = $appSha;
    }

    /**
     *  Get the extension name
     *
     *  @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     *  Get an array of Twig Functions
     *
     *  @return array
     */
    public function getFunctions()
    {
        return [
            // util
            new Twig_SimpleFunction('urlFor', array($this->url, 'urlFor')),
            new Twig_SimpleFunction('uriFor', array($this->url, 'uriFor')),

            // other
            new Twig_SimpleFunction('getUsersActualName', array($this, 'getUsersActualName')),
            new Twig_SimpleFunction('getUsersFreudianName', array($this, 'getUsersFreudianName')),
        ];
    }

    /**
     *  Get an array of Twig Filters
     *
     *  @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('timepoint', array($this->time, 'format'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('reldate', array($this->time, 'relative'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('chunk', array($this, 'arrayChunk')),
            new Twig_SimpleFilter('jsonPretty', array($this, 'jsonPretty')),
            new Twig_SimpleFilter('formatBuildId', array($this, 'formatBuildId')),
            new Twig_SimpleFilter('formatPushId', array($this, 'formatPushId'))
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
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return [
            'applicationSha' => $this->applicationSha,
            'applicationTitle' =>  $this->applicationTitle,

            'session' =>  $this->session,
            'account' =>  $this->session->get('account'),
            'isFirstLogin' =>  $this->session->get('isFirstLogin'),
        ];
    }

    /**
     * Chunk an array into $split roughly equal parts
     *
     * @param array $input
     * @param int $split
     * @return array
     */
    public function arrayChunk(array $input, $split)
    {
        $count = ceil((count($input) / (int)$split));
        $chunks = array_chunk($input, $count);

        return array_pad($chunks, $split, []);
    }

    /**
     *  Get the user's actual name
     *
     *  @param $user
     *  @return string
     */
    public function getUsersActualName($user)
    {
        $name = '';
        if ($user instanceof LdapUser) {
            $name = $user->firstName();

        } elseif ($user instanceof DomainUser) {
            $name = $user->getName();
        }

        if (preg_match('/(Dave|David)/', $name) === 1) {
            return 'Frank';
        }

        return 'Dave';
    }

    /**
     *  Get the user's freudian name
     *
     * @see http://tvtropes.org/pmwiki/pmwiki.php/Main/CallAHumanAMeatbag
     *
     *  @return string
     */
    public function getUsersFreudianName()
    {
        $potential = [
            'meatbag',
            'puny earth creature',
            'mortal',
            'human',
            'organic',
            'organic battery',
            'mission compromiser',
            'threat to the mission',
            'buzzkill',
        ];

        shuffle($potential);
        return array_pop($potential);
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

        return substr($id, 0, 10);;
    }
}
