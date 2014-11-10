<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class SettingsController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var UserRepository
     */
    private $userRepo;

    /**
     *  @var User
     */
    private $currentUser;

    /**
     * @param Twig_Template $template
     * @param UserRepository $userRepo
     * @param User $currentUser
     */
    public function __construct(
        Twig_Template $template,
        UserRepository $userRepo,
        User $currentUser
    ) {
        $this->template = $template;
        $this->userRepo = $userRepo;
        $this->currentUser = $currentUser;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     *
     * @return null
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        if (!$user = $this->userRepo->find($this->currentUser->getId())) {
            return call_user_func($notFound);
        }

        $rendered = $this->template->render([
            'user' => $user
        ]);

        $response->setBody($rendered);
    }
}
