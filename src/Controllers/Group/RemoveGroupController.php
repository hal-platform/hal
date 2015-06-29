<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Flasher;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveGroupController implements ControllerInterface
{
    const SUCCESS = 'Group "%s" removed.';
    const ERR_HAS_APPLICATIONS = 'Cannot remove group. All associated applications must first be removed.';

    /**
     * @type EntityRepository
     */
    private $groupRepo;
    private $applicationRepo;

    /**
     * @type EntityManagerInterface
     */
    private $entityManager;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        NotFound $notFound,
        array $parameters
    ) {
        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->em = $em;

        $this->flasher = $flasher;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$group = $this->groupRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($applications = $this->applicationRepo->findBy(['group' => $group])) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_APPLICATIONS, 'error')
                ->load('group', ['id' => $group->id()]);
        }

        $this->em->remove($group);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $group->name());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('groups');
    }
}
