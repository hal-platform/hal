<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Permissions;

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
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var EntityRepository
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
     * @inheritDoc
     */
    public function __invoke()
    {
        $types = $this->getTypes();

        // sort
        $sorter = $this->typeSorter();

        usort($types['pleb'], $sorter);
        usort($types['lead'], $sorter);
        usort($types['btn_pusher'], $sorter);
        usort($types['super'], $sorter);

        $rendered = $this->template->render([
            'userTypes' => $types
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
            $a = $a->user()->name();
            $b = $b->user()->name();

            return strcasecmp($a, $b);
        };
    }
}
