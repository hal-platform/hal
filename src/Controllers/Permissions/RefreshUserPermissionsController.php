<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Hal\Core\Entity\User;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RefreshUserPermissionsController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Permission Cache refreshed for "%s".';

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(AuthorizationService $authorizationService, URI $uri)
    {
        $this->authorizationService = $authorizationService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        $this->authorizationService->clearUserCache($user);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $user->username()));

        return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
    }
}
