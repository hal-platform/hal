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

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $permissions = $this->userPermissionsRepo->findAll();
        $types = $this->getTypes($permissions);

        // sort
        $sorter = $this->permissionSorter();

        usort($types[UserPermissionEnum::TYPE_MEMBER], $sorter);
        usort($types[UserPermissionEnum::TYPE_OWNER], $sorter);
        usort($types[UserPermissionEnum::TYPE_ADMIN], $sorter);
        usort($types[UserPermissionEnum::TYPE_SUPER], $sorter);

        return $this->withTemplate($request, $response, $this->template, [
            'user_types' => $types,
            'owners' => $this->collateByUser($types[UserPermissionEnum::TYPE_OWNER]),
            'deploy' => $this->collateByUser($permissions)
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
            UserPermissionEnum::TYPE_MEMBER => [],
            UserPermissionEnum::TYPE_OWNER => [],
            UserPermissionEnum::TYPE_ADMIN => [],
            UserPermissionEnum::TYPE_SUPER => [],
        ];

        /** @var UserPermission $permission */
        foreach ($permissions as $permission) {
            $collated[$permission->type()][] = $permission;
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
            return strcasecmp($a['user']->handle(), $b['user']->handle());
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
