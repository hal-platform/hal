<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminRemoveHandle
{
    /**
     * @var RepositoryRepository
     */
    private $repoRepo;

    /**
     * @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param RepositoryRepository $repoRepo
     * @param DeploymentRepository $deploymentRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        RepositoryRepository $repoRepo,
        DeploymentRepository $deploymentRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->repoRepo = $repoRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['id'])) {
            return $notFound();
        }

        if ($deployments = $this->deploymentRepo->findBy(['repository' => $repo])) {
            $this->session->flash('Cannot remove repository. All server deployments must first be removed.', 'error');
            return $this->url->redirectFor('repository'. ['id' => $repo->getId()]);
        }

        $this->entityManager->remove($repo);
        $this->entityManager->flush();

        $message = sprintf('Repository "%s" removed.', $repo->getKey());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('repositories');
    }
}
