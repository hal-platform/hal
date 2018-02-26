<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security\UserAuthentication;

use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Service\UserIdentityService;
use Hal\UI\Security\OAuthProviderFactory;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Provider\AbstractProvider;

use GuzzleHttp\Exception\BadResponseException;
use UnexpectedValueException;
use Exception;

class OAuth
{
    use ValidatorErrorTrait;

    const ERR_IDENTITY_NOT_FOUND = 'Invalid sign-in information. Please try again.';
    const ERR_IDP_MISCONFIGURED = 'Internal Auth Identity Provider is misconfigured.';
    const ERR_BAD_VERIFICATION_CODE = 'An error occured. Please try again.';
    const ERR_AUTH_MISCONFIGURED = 'No valid Identity Provider was found. Hal may be misconfigured.';

    /**
     * @var UserIdentityService
     */
    private $userIdentityService;

    /**
     * @var OAuthProviderFactory
     */
    private $providerFactory;

    /**
     * @param UserIdentityService $userIdentityService
     */
    public function __construct(
        UserIdentityService $userIdentityService,
        OAuthProviderFactory $providerFactory
    ) {
        $this->userIdentityService = $userIdentityService;
        $this->providerFactory = $providerFactory;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string|null $code
     *
     * @return User|null
     */
    public function authenticate(UserIdentityProvider $idp, $code = ''): ?User
    {
        $user = $this->getResourceOwner($idp, $code);

        if ($this->hasErrors()) {
            return null;
        }

        return $this->getUserData($idp, $user);
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string|null $code
     *
     * @return ResourceOwnerInterface|null
     */
    public function getResourceOwner(UserIdentityProvider $idp, $code = ''): ?ResourceOwnerInterface
    {
        $data = ['code' => $code];
        $provider = $this->providerFactory->getProvider($idp, $data) ?? null;

        if (!$provider instanceof AbstractProvider) {
            $this->addError(self::ERR_AUTH_MISCONFIGURED);
            return null;
        }

        try {
            $token = $provider->getAccessToken('authorization_code', ['code' => $code]);
        } catch (Exception $e) {
            $this->addError(self::ERR_BAD_VERIFICATION_CODE);
            return null;
        }

        try {
            $user = $provider->getResourceOwner($token);
        } catch (UnexpectedValueException $e) {
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        } catch (BadResponseException $e) {
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        }

        return $user;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param ResourceOwnerInterface $user
     *
     * @return User|null
     */
    private function getUserData(UserIdentityProvider $idp, ResourceOwnerInterface $user)
    {
        if (!$idp->isOAuth()) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        return $this->userIdentityService->getOrCreateUser($idp, $user);
    }
}
