<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security\UserAuthentication;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface as GuzzleInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Parameters;
use Hal\Core\Type\IdentityProviderEnum;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github as GitHubProvider;

class GitHubEnterpriseAuth extends GitHubAuth
{
    /**
     * @var GuzzleInterface
     */
    private $guzzle;

    /**
     * @var OAuthCallbackFactory
     */
    private $callbackFactory;

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
        parent::__construct($em, $guzzle, $callbackFactory, $requiredScopes);

        $this->guzzle = $guzzle;
        $this->callbackFactory = $callbackFactory;
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return bool
     */
    protected function isSupported(UserIdentityProvider $idp)
    {
        return ($idp->type() === IdentityProviderEnum::TYPE_GITHUB_ENTERPRISE);
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return AbstractProvider
     */
    protected function getClient(UserIdentityProvider $idp)
    {
        $id = $idp->parameter(Parameters::IDP_GHE_CLIENT_ID);
        $secret = $idp->parameter(Parameters::IDP_GHE_CLIENT_SECRET);
        $domain = $idp->parameter(Parameters::IDP_GHE_URL);

        $data = [
            'clientId' => $id,
            'clientSecret' => $secret,
            'redirectUri' => $this->callbackFactory->getFullCallbackURL(),

            'domain' => $domain
        ];

        $provider = new GitHubProvider($data, [
            'httpClient' => $this->guzzle
        ]);

        return $provider;
    }
}
