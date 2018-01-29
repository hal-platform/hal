<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\IdentityProviders;

use Hal\Core\Entity\System\UserIdentityProvider;
use Psr\Http\Message\ServerRequestInterface;

interface IdentityProviderValidatorInterface
{
    /**
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isValid(array $parameters): ?UserIdentityProvider;

    /**
     * @param UserIdentityProvider $provider
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isEditValid(UserIdentityProvider $provider, array $parameters): ?UserIdentityProvider;

    /**
     * @param ServerRequestInterface $request
     * @param UserIdentityProvider|null $provider
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?UserIdentityProvider $provider): array;

    /**
     * @return array
     */
    public function errors(): array;
}
