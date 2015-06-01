<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
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
class DeploymentPermissionsController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type EntityRepository
     */
    private $userRepo;
    private $userTypesRepo;

    /**
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->currentUser = $currentUser;

        $this->userPermissionsRepo = $em->getRepository(UserPermission::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $permissions = $this->getPermissions();

        // sort
        $sorter = $this->typeSorter();

        usort($permissions['prod'], $sorter);
        usort($permissions['non_prod'], $sorter);

        $rendered = $this->template->render([
            'userPermissions' => $permissions
        ]);
    }

    /**
     * Get all user permissions in the whole db, collated into per-type buckets
     *
     * @return array
     */
    private function getPermissions()
    {
        $userPermissions = $this->userPermissionsRepo->findAll();

        $collated = [
            'prod' => [],
            'non_prod' => [],
            'current' => []
        ];

        foreach ($userPermissions as $userPermission) {

            if ($userPermission->isProduction()) {
                $collated['prod'][] = $userPermission;

            } else {
                $collated['non_prod'][] = $userPermission;
            }

            if ($userPermission->user() === $this->currentUser) {
                $collated['current'][] = $userPermission;
            }
        }

        return $collated;
    }

    /**
     * @return Closure
     */
    private function typeSorter()
    {
        return function(UserPermission $a, UserPermission $b) {
            $a = $a->user()->name();
            $b = $b->user()->name();

            return strcasecmp($a, $b);
        };
    }
}
