<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal;

use QL\MCP\Common\GUID;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;

class Auth
{
    /**
     * @var Ldap
     */
    private $ldap;

    /**
     * @var string
     */
    private $baseDN;

    private $domain;
    private $userObject;
    private $usernameAttribute;

    /**
     * @todo clean this up
     *
     * @param Ldap $ldap
     * @param string $baseDN
     */
    public function __construct(Ldap $ldap, $baseDN, $domain, $userObject, $usernameAttribute)
    {
        $this->ldap = $ldap;
        $this->baseDN = $baseDN;

        $this->domain = $domain;
        $this->userObject = $userObject;
        $this->usernameAttribute = $usernameAttribute;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return array|null
     */
    public function authenticate($username, $password)
    {
        // @todo escape

        $user = sprintf('%s\%s', $this->domain, $username);

        try {
            $this->ldap->bind($user, $password);

        // Symfony suppresses errors, but our error handler does not properly ignore suppressed errors.
        } catch (\Exception $ex) {
            return null;
        }

        $query = sprintf('(&(objectclass=%s)(%s=%s))', $this->userObject, $this->usernameAttribute, $username);

        $attributes = [
            'id' => 'objectGUID',
            'username' => $this->usernameAttribute,
            'email' => 'mail',
            'name' => 'name'
        ];

        $query = $this->ldap->query($this->baseDN, $query, [
            'filter' => array_values($attributes)
        ]);

        $results = $query->execute();

        if (count($results) !== 1) {
            return null;
        }

        $info = $results[0];

        $guid = $info->getAttribute($attributes['id'])[0];
        $user = [
            'id' => unpack("H*hex", $guid)['hex'],
            'username' => strtolower($info->getAttribute($attributes['username'])[0]),
            'email' => $info->getAttribute($attributes['email'])[0],
            'name' => $info->getAttribute($attributes['name'])[0]
        ];

        foreach ($user as $val) {
            if (strlen($val) === 0) return null;
        }

        return $user;
    }
}
