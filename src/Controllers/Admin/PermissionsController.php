<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
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
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type Response
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

        $this->userTypesRepo = $em->getRepository(UserType::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $types = $this->getTypes();

        // Get current users permissions
        $isSuper = $isButtonPusher = false;
        foreach ($types['current'] as $currentType) {
            if ($currentType->type() === 'btn_pusher') {
                $isSuper = true;
            } elseif ($currentType->type() === 'super') {
                $isButtonPusher = true;
            }
        }

        // sort
        $sorter = $this->typeSorter();

        usort($types['pleb'], $sorter);
        usort($types['lead'], $sorter);
        usort($types['btn_pusher'], $sorter);
        usort($types['super'], $sorter);

        $rendered = $this->template->render([
            'userTypes' => $types,
            'isCurrentUserSuper' => $isSuper,
            'isCurrentUserAdmin' => $isButtonPusher
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
            'super' => [],
            'current' => []
        ];

        foreach ($userTypes as $userType) {
            $type = $userType->type();

            $collated[$type][] = $userType;

            if ($userType->user() === $this->currentUser) {
                $collated['current'][] = $userType;
            }
        }

        return $collated;
    }

    /**
     * @return Closure
     */
    private function typeSorter()
    {
        return function(UserType $a, UserType $b) {
            $a = $a->user()->getName();
            $b = $b->user()->getName();

            return strcasecmp($a, $b);
        };
    }
}
