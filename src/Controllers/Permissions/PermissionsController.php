<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * Super:
 *     Add any.
 *     Remove Lead, ButtonPusher
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead
 *
 */
class PermissionsController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $userPermissionsRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->userPermissionsRepo = $em->getRepository(UserPermission::class);
    }

    /**l
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $permissions = $this->userPermissionsRepo->findAll();
        $types = $this->getTypes($permissions);

        // sort
        $sorter = $this->permissionSorter();

        usort($types['member'], $sorter);
        usort($types['owner'], $sorter);
        usort($types['admin'], $sorter);

        return $this->withTemplate($request, $response, $this->template, [
            'user_types' => $types,
            'members' => $this->collateByUser($types['member']),
            'owners' => $this->collateByUser($types['owner'] ?? []),
            'admins' => $this->collateByUser($types['admin'] ?? [])
        ]);
    }

    /**
     * Get all bucket all permissions into their types
     *
     * @param array $permissions
     *
     * @return array
     */
    private function getTypes(array $permissions)
    {
        $collated = [
            'member' => [],
            'owner' => [],
            'admin' => [],
        ];

        /** @var UserPermission $permission */
        foreach ($permissions as $permission) {
            if ($permission->type() === UserPermissionEnum::TYPE_ADMIN ||
                $permission->type() === UserPermissionEnum::TYPE_SUPER
            ) {
                $type = 'admin';
            } else {
                $type = $permission->type();
            }

            $collated[$type][] = $permission;
        }

        return $collated;
    }

    /**
     * @param array $permissions
     *
     * @return array
     */
    private function collateByUser(array $permissions)
    {
        $users = [];

        foreach ($permissions as $t) {
            if (!isset($users[$t->user()->id()])) {
                $users[$t->user()->id()] = [
                    'user' => $t->user(),
                    'permissions' => []
                ];
            }

            $users[$t->user()->id()]['permissions'][] = $t;
        }

        usort($users, function ($a, $b) {
            return strcasecmp($a['user']->username(), $b['user']->username());
        });

        return $users;
    }


    /**
     * @return callable
     */
    private function permissionSorter()
    {
        return function ($a, $b) {
            $a = $a->user()->username();
            $b = $b->user()->username();

            return strcasecmp($a, $b);
        };
    }
}
