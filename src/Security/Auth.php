<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;

class Auth
{
    use ValidatorErrorTrait;

    const ERR_AUTH_MISCONFIGURED = 'No valid Identity Provider was found. Hal may be misconfigured.';

    /**
     * @var array
     */
    private $adapters;

    /**
     * @param array $adapters
     */
    public function __construct(array $adapters = [])
    {
        $this->adapters = [];

        foreach ($adapters as $type => $adapter) {
            $this->addAdapter($type, $adapter);
        }
    }

    /**
     * @param string $type
     * @param mixed $adapter
     *
     * @return void
     */
    public function addAdapter($type, $adapter): void
    {
        $this->adapters[$type] = $adapter;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param array $data
     *
     * @return User|null
     */
    public function authenticate(UserIdentityProvider $idp, array $data = []): ?User
    {
        $adapter = $this->adapters[$idp->type()] ?? null;
        if (!$adapter) {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        if ($idp->type() === IdentityProviderEnum::TYPE_INTERNAL) {
            $user = $adapter->authenticate($idp, $data['username'] ?? '', $data['password'] ?? '');

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_LDAP) {
            $user = $adapter->authenticate($idp, $data['username'] ?? '', $data['password'] ?? '');

        } else {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
