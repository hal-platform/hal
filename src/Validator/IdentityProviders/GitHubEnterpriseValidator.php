<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\IdentityProviders;

use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class GitHubEnterpriseValidator implements IdentityProviderValidatorInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    /**
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isValid(array $parameters): ?UserIdentityProvider
    {
        $this->resetErrors();

        return null;
    }

    /**
     * @param UserIdentityProvider $provider
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isEditValid(UserIdentityProvider $provider, array $parameters): ?UserIdentityProvider
    {
        $this->resetErrors();

        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param UserIdentityProvider|null $provider
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?UserIdentityProvider $provider): array
    {
        $data = $request->getParsedBody();

        return [];
    }
}
