<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\UserType;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

/**
 * Super:
 *     Add any.
 *     Remove Lead, ButtonPusher
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead
 */
class RemovePermissionsController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'User Permission "%s" revoked from "%s".';

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param PermissionService $permissions
     * @param URI $uri
     */
    public function __construct(PermissionService $permissions, URI $uri)
    {
        $this->permissions = $permissions;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $userType = $request->getAttribute(UserType::class);

        $map = [
            'pleb' => 'Standard',
            'lead' => 'Owner',
            'btn_pusher' => 'Admin',
            'super' => 'Super'
        ];

        $type = $map[$userType->type()];
        $name = $userType->user()->handle();

        $this->permissions->removeUserPermissions($userType);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $type, $name));
        return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
    }
}
