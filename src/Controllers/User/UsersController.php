<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @param TemplateInterface $template
     * @param UserRepository $userRepo
     */
    public function __construct(TemplateInterface $template, UserRepository $userRepo)
    {
        $this->template = $template;
        $this->userRepo = $userRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $users = $this->userRepo->findBy([], ['name' => 'ASC']);

        $active = [];
        $inactive = [];

        foreach ($users as $user) {
            if ($user->isActive()) {
                $active[] = $user;
            } else {
                $inactive[] = $user;
            }
        }

        $context = [
            'users' => $active,
            'inactiveUsers' => $inactive
        ];

        $rendered = $this->template->render($context);
        $response->setBody($rendered);
    }
}
