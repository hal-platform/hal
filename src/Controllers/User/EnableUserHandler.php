<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class EnableUserHandler implements ControllerInterface
{
    const SUCCESS = 'User Enabled.';

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
            ->withIsActive(true);

        $this->em->merge($this->selectedUser);
        $this->em->flush();

        $this->flasher
            ->withFlash(self::SUCCESS, 'success')
            ->load('user', ['user' => $this->selectedUser->id()]);
    }
}
