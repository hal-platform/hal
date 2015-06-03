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
use QL\Hal\Core\Entity\Token;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class SettingsController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $tokenRepo;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param User $currentUser
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        User $currentUser
    ) {
        $this->template = $template;
        $this->tokenRepo = $em->getRepository(Token::CLASS);

        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->template->render([
            'user' => $this->currentUser,
            'tokens' => $this->tokenRepo->findBy(['user' => $this->currentUser]),
            'hasGithubToken' => (strlen($this->currentUser->githubToken()) > 0)
        ]);
    }
}
