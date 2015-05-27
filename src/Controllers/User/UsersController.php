<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Repository\UserRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

/**
 * @todo paginate this stupid page.
 */
class UsersController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $userRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, Response $response)
    {
        $this->template = $template;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->userTypesRepo = $em->getRepository(UserType::CLASS);

        $this->response = $response;
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

            $id = $user->getId();
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

        $rendered = $this->template->render($context);
        $this->response->setBody($rendered);
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

            $userId = $type->user()->getId();
            if (!isset($collated[$userId])) {
                $collated[$userId] = ['hasType' => true];
            }

            $collated[$userId][$flag] = true;
        }

        return $collated;
    }
}
