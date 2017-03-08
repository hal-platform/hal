<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class UserController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @param TemplateInterface $template
     * @param PermissionService $permissions
     */
    public function __construct(TemplateInterface $template, PermissionService $permissions)
    {
        $this->template = $template;
        $this->permissions = $permissions;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        $userPerm = $this->permissions->getUserPermissions($user);
        $appPerm = $this->permissions->getApplications($userPerm);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'user_permission' => $userPerm,
            'lead_applications' => $appPerm['lead'],
            'prod_applications' => $appPerm['prod'],
            'non_prod_applications' => $appPerm['non_prod']
        ]);
    }
}
