<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Repository\User\UserPermissionRepository;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PermissionsController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var UserPermissionRepository
     */
    private $permissionRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->permissionRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $page = $this->getCurrentPage($request);

        $paginator = $this->permissionRepo->getPagedResults(SharedStaticConfiguration::MASSIVE_PAGE_SIZE, ($page - 1));
        $last = $this->getLastPage($paginator, SharedStaticConfiguration::MASSIVE_PAGE_SIZE);
        $permissions = $this->getEntitiesForPage($paginator);

        [$permissions, $adminCount] = $this->collateByUser($permissions);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'permissions' => $permissions,

            // @todo - this is naive - only counts the current size
            // Need a separate service to track number of users of a type
            'admin_count' => $adminCount,
        ]);
    }

    /**
     * @param array $permissions
     *
     * @return array
     */
    private function collateByUser(array $permissions)
    {
        $users = [];

        $adminCount = 0;

        foreach ($permissions as $perm) {
            $id = $perm->user()->id();

            if (!isset($users[$id])) {
                $users[$id] = [
                    'user' => $perm->user(),
                    'permissions' => [],
                ];
            }

            if ($perm->type() === UserPermissionEnum::TYPE_ADMIN || $perm->type() === UserPermissionEnum::TYPE_SUPER) {
                $adminCount++;
            }

            $users[$id]['permissions'][] = $perm;
        }

        usort($users, function ($a, $b) {
            return strcasecmp($a['user']->name(), $b['user']->name());
        });

        return [$users, $adminCount];
    }
}
