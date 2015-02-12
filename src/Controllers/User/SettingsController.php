<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class SettingsController implements ControllerInterface
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
     * @type User
     */
    private $currentUser;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param UserRepository $userRepo
     * @param User $currentUser
     * @param Response $response
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        UserRepository $userRepo,
        User $currentUser,
        Response $response,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->userRepo = $userRepo;
        $this->currentUser = $currentUser;

        $this->response = $response;
        $this->notFound = $notFound;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$user = $this->userRepo->find($this->currentUser->getId())) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'user' => $user,
            'hasGithubToken' => (strlen($user->getGithubToken()) > 0)
        ]);

        $this->response->setBody($rendered);
    }
}
