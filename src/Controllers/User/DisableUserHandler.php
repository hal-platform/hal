<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class DisableUserHandler implements ControllerInterface
{
    const SUCCESS = 'User Disabled.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type User
     */
    private $selectedUser;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param User $selectedUser
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        User $selectedUser
    ) {
        $this->em = $em;
        $this->flasher = $flasher;
        $this->selectedUser = $selectedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->selectedUser
            ->withIsActive(false);

        $this->em->merge($this->selectedUser);
        $this->em->flush();

        $this->flasher
            ->withFlash(self::SUCCESS, 'success')
            ->load('user', ['user' => $this->selectedUser->id()]);
    }
}
