<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\User;
use Psr\Http\Message\ServerRequestInterface;

interface UserAuthenticationInterface
{
    /**
     * @param UserIdentityProvider $idp
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    public function getProviderData(UserIdentityProvider $idp, ServerRequestInterface $request): array;

    /**
     * @param UserIdentityProvider $idp
     * @param string ...$parameters
     *
     * @return User|null
     */
    public function authenticate(UserIdentityProvider $idp, string ...$parameters): ?User;
}
