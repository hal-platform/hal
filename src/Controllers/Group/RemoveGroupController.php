<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Repository\GroupRepository;
use QL\Hal\Core\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveGroupController implements ControllerInterface
{
    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        NotFound $notFound,
        array $parameters
    ) {
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;

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

        if ($repos = $this->repoRepo->findBy(['group' => $group])) {
            $this->session->flash('Cannot remove group. All associated repositories must first be removed.', 'error');
            return $this->url->redirectFor('groups', ['id' => $group->getId()]);
        }

        $this->entityManager->remove($group);
        $this->entityManager->flush();

        $message = sprintf('Group "%s" removed.', $group->getName());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('groups');
    }
}
