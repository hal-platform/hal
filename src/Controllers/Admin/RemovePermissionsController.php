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
class RemovePermissionsController implements ControllerInterface
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

        $rendered = $this->template->render([]);
    }
}
