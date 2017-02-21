<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Panthor\MiddlewareInterface;
use Slim\Http\Request;

class RemoveEncryptedPropertyHandler implements MiddlewareInterface
{
    const SUCCESS = 'Encrypted Property "%s" removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EncryptedProperty
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
     * @inheritDoc
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
