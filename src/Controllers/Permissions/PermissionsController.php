<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
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
    private $userTypesRepo;
    private $userPermissionsRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->userTypesRepo = $em->getRepository(UserType::class);
        $this->userPermissionsRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $types = $this->getTypes();
        $permissions = $this->userPermissionsRepo->findAll();

        // sort
        $sorter = $this->permissionSorter();

        usort($types['pleb'], $sorter);
        usort($types['btn_pusher'], $sorter);
        usort($types['super'], $sorter);

        return $this->withTemplate($request, $response, $this->template, [
            'user_types' => $types,
            'owners' => $this->collateByUser($types['lead']),
            'deploy' => $this->collateByUser($permissions)
        ]);
    }

    /**
     * Get all user types in the whole db, collated into per-type buckets
     *
     * @return array
     */
    private function getTypes()
    {
        $userTypes = $this->userTypesRepo->findAll();

        $collated = [
            'pleb' => [],
            'lead' => [],
            'btn_pusher' => [],
            'super' => []
        ];

        foreach ($userTypes as $userType) {
            $type = $userType->type();

            $collated[$type][] = $userType;
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

        usort($users, function($a, $b) {
            return strcasecmp($a['user']->handle(), $b['user']->handle());
        });

        return $users;
    }


    /**
     * @return callable
     */
    private function permissionSorter()
    {
        return function($a, $b) {
            $a = $a->user()->handle();
            $b = $b->user()->handle();

            return strcasecmp($a, $b);
        };
    }
}
