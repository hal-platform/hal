<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Flasher;
use QL\Panthor\MiddlewareInterface;
use Slim\Http\Request;

class RemoveEncryptedPropertyHandler implements MiddlewareInterface
{
    const SUCCESS = 'Encrypted Property "%s" Removed.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type EncryptedProperty
     */
    private $encrypted;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Request $request
     * @param Application $application
     * @param EncryptedProperty $encrypted
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        Request $request,
        EncryptedProperty $encrypted
    ) {
        $this->em = $em;

        $this->flasher = $flasher;
        $this->request = $request;

        $this->encrypted = $encrypted;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $this->em->remove($this->encrypted);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $this->encrypted->name());
        $this->flasher
            ->withFlash($message, 'success')
            ->load('encrypted', ['application' => $this->encrypted->application()->id()]);
    }
}
