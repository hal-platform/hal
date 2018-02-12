<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Hal\Core\Type\IdentityProviderEnum;
use Hal\Core\Entity\System\UserIdentityProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github as GitHubProvider;

use Hal\UI\Validator\IdentityProviders\GitHubEnterpriseValidator;
use Hal\UI\Validator\IdentityProviders\GitHubValidator;

class OAuthProviderFactory
{
    /**
     * @param UserIdentityProvider $idp
     * @param array $data
     *
     * @return AbstractProvider|null
     */
    public function getProvider(UserIdentityProvider $idp, $data = []): ?AbstractProvider
    {
        $providerType = $idp->type();

        if ($providerType === IdentityProviderEnum::TYPE_GITHUB_ENTERPRISE) {
            $options = [
                'clientId' => $idp->parameter(GitHubEnterpriseValidator::ATTR_CLIENT_ID),
                'clientSecret' => $idp->parameter(GitHubEnterpriseValidator::ATTR_CLIENT_SECRET),
                'domain' => $idp->parameter(GitHubEnterpriseValidator::ATTR_DOMAIN),
                'redirectUri' => $data['redirect_uri'] ?? '',
                'scope' => $data['scope'] ?? ''
            ];

            return new GitHubProvider($options);

        } else if ($providerType === IdentityProviderEnum::TYPE_GITHUB) {
            $options = [
                'clientId' => $idp->parameter(GitHubValidator::ATTR_CLIENT_ID),
                'clientSecret' => $idp->parameter(GitHubValidator::ATTR_CLIENT_SECRET),
                'apiDomain' => $idp->parameter(GitHubValidator::ATTR_API_DOMAIN),
                'redirectUri' => $data['redirect_uri'] ?? '',
                'scope' => $data['scope'] ?? ''
            ];

            return new GitHubProvider($options);
        }

        return null;
    }
}
