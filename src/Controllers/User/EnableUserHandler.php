<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Flasher;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;

class EnableUserHandler implements ControllerInterface
{
    const SUCCESS = 'User Enabled.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var User
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
     * @inheritDoc
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
