<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class RemoveCredentialHandler implements ControllerInterface
{
    const SUCCESS = 'Credential "%s" removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Credential
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

        // Deployment caches must be manually flushed here, since they would contain a link to a removed entity
        $cache = $this->em->getCache();
        $cache->evictEntityRegion(Deployment::CLASS);

        $message = sprintf(self::SUCCESS, $this->credential->name());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('admin.credentials');
    }
}
