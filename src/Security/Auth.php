<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\User;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param UserAuthenticationInterface $adapter
     *
     * @return void
     */
    public function addAdapter($type, UserAuthenticationInterface $adapter): void
    {
        $this->adapters[$type] = $adapter;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param ServerRequestInterface $request
     *
     * @return array|null
     */
    public function prepare(UserIdentityProvider $idp, ServerRequestInterface $request): ?array
    {
        $adapter = $this->adapters[$idp->type()] ?? null;
        if (!$adapter) {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        $data = $adapter->getProviderData($idp, $request);
        if (!$data) {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        return $data;
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

        // internal
        $user = $data['username'] ?? '';
        $pass = $data['password'] ?? '';

        // external
        $code = $data['code'] ?? '';
        $state = $data['state'] ?? '';
        $storedState = $data['stored_state'] ?? '';

        if ($idp->type() === IdentityProviderEnum::TYPE_INTERNAL) {
            $user = $adapter->authenticate($idp, $user, $pass);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_LDAP) {
            $user = $adapter->authenticate($idp, $user, $pass);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_GITHUB_ENTERPRISE) {
            $user = $adapter->authenticate($idp, $code, $state, $storedState);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_GITHUB) {
            $user = $adapter->authenticate($idp, $code, $state, $storedState);

        } else {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        if ($user instanceof User) {
            return $user;
        }

        $this->importErrors($adapter->errors());

        return null;
    }
}
