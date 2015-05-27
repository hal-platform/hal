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
use QL\Panthor\Slim\NotFound;
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
     * @type EntityRepository
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
     * @param EntityManagerInterface $em
     * @param User $currentUser
     * @param Response $response
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        User $currentUser,
        Response $response,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->userRepo = $em->getRepository(User::CLASS);
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
