<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Client\Provider\AbstractProvider;

class Auth
{
    use ValidatorErrorTrait;

    const ERR_AUTH_MISCONFIGURED = 'No valid Identity Provider was found. Hal may be misconfigured.';

    /**
     * @var array
     */
    private $adapters;

    /**
     * @var OAuthProviderFactory
     */
    private $providerFactory;

    /**
     * @param OAuthProviderFactory $providerFactory
     * @param array $adapters
     */
    public function __construct(OAuthProviderFactory $providerFactory, array $adapters = [])
    {
        $this->providerFactory = $providerFactory;

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

        $user = $data['username'] ?? '';
        $pass = $data['password'] ?? '';
        $code = $data['code'] ?? '';

        if ($idp->type() === IdentityProviderEnum::TYPE_INTERNAL) {
            $user = $adapter->authenticate($idp, $user, $pass);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_LDAP) {
            $user = $adapter->authenticate($idp, $user, $pass);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_GITHUB_ENTERPRISE) {
            $user = $adapter->authenticate($idp, $code);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_GITHUB) {
            $user = $adapter->authenticate($idp, $code);

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

    /**
     * @param UserIdentityProvider $idp
     * @param array $data
     *
     * return string|null
     */
    public function authorize(UserIdentityProvider $idp, array $data = []): ?string
    {
        $provider = $this->providerFactory->getProvider($idp, $data) ?? null;

        if (!$provider instanceof AbstractProvider) {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        return $provider->authorize($data, function (string $uri) {
            return $uri;
        });
    }
}
