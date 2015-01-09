<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class UsersController implements ControllerInterface
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
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param UserRepository $userRepo
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, UserRepository $userRepo, Response $response)
    {
        $this->template = $template;
        $this->userRepo = $userRepo;
        $this->response = $response;
    }


    /**
     * {@inheritdoc}
     */
    public function __invoke()
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
        $this->response->setBody($rendered);
    }
}
