<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security\UserAuthentication;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\ClientInterface as GuzzleInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserIdentity;
use Hal\Core\Parameters;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Security\UserAuthenticationInterface;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use League\OAuth2\Client\Grant\Exception\InvalidGrantException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Github as GitHubProvider;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ServerRequestInterface;
use function random_bytes;

class GitHubAuth implements UserAuthenticationInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;

    public const AUTO_CREATE_USER = 1;
    public const DEFAULT_FLAGS = self::AUTO_CREATE_USER;

    private const SECRET_BYTES = 32;

    private const ERR_INVALID_STATE = 'An error occurred when verifying GitHub OAuth response. Please try again.';
    private const ERR_IDENTITY_NOT_FOUND = 'Invalid sign-in information. Please try again.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $identityRepo;

    /**
     * @var GuzzleInterface
     */
    private $guzzle;

    /**
     * @var OAuthCallbackFactory
     */
    private $callbackFactory;

    /**
     * @var array
     */
    private $requiredScopes;

    /**
     * @param EntityManagerInterface $em
     * @param GuzzleInterface $guzzle
     * @param OAuthCallbackFactory $callbackFactory
     * @param array $requiredScopes
     */
    public function __construct(
        EntityManagerInterface $em,
        GuzzleInterface $guzzle,
        OAuthCallbackFactory $callbackFactory,
        array $requiredScopes
    ) {
        $this->em = $em;
        $this->identityRepo = $em->getRepository(UserIdentity::class);

        $this->guzzle = $guzzle;
        $this->callbackFactory = $callbackFactory;
        $this->requiredScopes = $requiredScopes;

        $this->withFlag(self::DEFAULT_FLAGS);
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string ...$parameters
     *              - string $code
     *              - string $state
     *              - string $storedState
     *
     * @return User|null
     */
    public function authenticate(UserIdentityProvider $idp, string ...$parameters): ?User
    {
        [$code, $state, $storedState] = $parameters;

        $isValid = hash_equals($state, $storedState);

        if (!$code || !$state || !$storedState || !$isValid) {
            $this->addError(self::ERR_INVALID_STATE);
            return null;
        }

        $ro = $this->getResourceOwner($idp, $code);

        if (!$ro) {
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        }

        $identity = $this->identityRepo->findOneBy([
            'provider' => $idp,
            'providerUniqueID' => $ro->getId(),
        ]);

        if ($identity instanceof UserIdentity) {
            return $identity->user();
        }

        if ($this->isFlagEnabled(self::AUTO_CREATE_USER)) {
            return $this->autoCreateUser($idp, $ro);
        }

        $this->addError(self::ERR_IDENTITY_NOT_FOUND);
        return null;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    public function getProviderData(UserIdentityProvider $idp, ServerRequestInterface $request): array
    {
        if (!$this->isSupported($idp)) {
            return [];
        }

        $client = $this->getClient($idp);

        $key = sprintf('%s:%s:%s', $idp->id(), implode(',', $this->requiredScopes), bin2hex(random_bytes(self::SECRET_BYTES)));
        $state = hash('sha256', $key);

        $requestData = [
            'state' => $state,
            'scope' => $this->requiredScopes,
        ];

        $uri = $client->authorize($requestData, function ($uri) {
            return $uri;
        });

        return $requestData + [
            'external' => $uri,
        ];
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string|null $code
     *
     * @return GithubResourceOwner|null
     */
    public function getResourceOwner(UserIdentityProvider $idp, $code): ?GithubResourceOwner
    {
        $client = $this->getClient($idp);

        $requestData = [
            'code' => $code,
        ];

        try {
            $token = $client->getAccessToken('authorization_code', $requestData);

            if ($this->validateScopesWereGranted($token)) {
                $ro = $client->getResourceOwner($token);
            } else {
                $ro = null;
            }

        } catch (InvalidGrantException $e) {
            $ro = null;

        } catch (IdentityProviderException $e) {
            $ro = null;
        }

        if ($ro instanceof GithubResourceOwner) {
            return $ro;
        }

        return null;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param GithubResourceOwner $owner
     *
     * @return User
     */
    private function autoCreateUser(UserIdentityProvider $idp, GithubResourceOwner $owner): User
    {
        $id = $owner->getId();
        $username = $owner->getNickname();

        $user = (new User)
            ->withName(strtolower($username));

        $identity = (new UserIdentity)
            ->withProviderUniqueID($id)
            ->withParameter(Parameters::ID_GH_ID, $id)
            ->withParameter(Parameters::ID_GH_USERNAME, $username)
            ->withUser($user)
            ->withProvider($idp);

        $this->em->persist($identity);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param AccessToken $token
     *
     * @return bool
     */
    private function validateScopesWereGranted(AccessToken $token)
    {
        $values = $token->getValues();
        $grantedScopes = explode(',', $values['scope'] ?? '');

        foreach ($this->requiredScopes as $scope) {
            if (!in_array($scope, $grantedScopes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return bool
     */
    protected function isSupported(UserIdentityProvider $idp)
    {
        return ($idp->type() === IdentityProviderEnum::TYPE_GITHUB);
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return AbstractProvider
     */
    protected function getClient(UserIdentityProvider $idp)
    {
        $id = $idp->parameter(Parameters::IDP_GH_CLIENT_ID);
        $secret = $idp->parameter(Parameters::IDP_GH_CLIENT_SECRET);

        $data = [
            'clientId' => $id,
            'clientSecret' => $secret,
            'redirectUri' => $this->callbackFactory->getFullCallbackURL(),
        ];

        $provider = new GitHubProvider($data, [
            'httpClient' => $this->guzzle,
        ]);

        return $provider;
    }
}
