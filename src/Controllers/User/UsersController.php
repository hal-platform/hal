<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Repository\UserRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * @todo paginate this stupid page.
 */
class UsersController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->userTypesRepo = $em->getRepository(UserType::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $users = $this->userRepo->findBy([], ['name' => 'ASC']);

        $userTypes = $this->getTypes();

        $active = [];
        $inactive = [];

        foreach ($users as $user) {
            if (!$user->isActive()) {
                $inactive[] = [
                    'user' => $user
                ];

                continue;
            }

            $id = $user->id();
            $types = isset($userTypes[$id]) ? $userTypes[$id] : [];

            $active[] = [
                'user' => $user,
                'type' => $types
            ];
        }

        $context = [
            'users' => $active,
            'inactiveUsers' => $inactive
        ];

        $this->template->render($context);
    }

    /**
     * Get all user types in the whole db, collated into per-user buckets
     *
     * @return array
     */
    private function getTypes()
    {
        $types = $this->userTypesRepo->findAll();

        $collated = [];

        foreach ($types as $type) {
            if ($type->type() === 'pleb') {
                $flag = 'isPleb';
            } elseif ($type->type() === 'lead') {
                $flag = 'isLead';
            } elseif ($type->type() === 'btn_pusher') {
                $flag = 'isButtonPusher';
            } elseif ($type->type() === 'super') {
                $flag = 'isSuper';
            }

            $userId = $type->user()->id();
            if (!isset($collated[$userId])) {
                $collated[$userId] = ['hasType' => true];
            }

            $collated[$userId][$flag] = true;
        }

        return $collated;
    }
}
