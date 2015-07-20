<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class RemoveCredentialHandler implements ControllerInterface
{
    const SUCCESS = 'Credential "%s" removed.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Credential
     */
    private $credential;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Credential $credential
     */
    public function __construct(EntityManagerInterface $em, Flasher $flasher, Credential $credential)
    {
        $this->em = $em;

        $this->flasher = $flasher;
        $this->credential = $credential;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->em->remove($this->credential);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $this->credential->name());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('admin.credentials');
    }
}
