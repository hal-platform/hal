<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminRemoveHandle
{
    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

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
     * @param DeploymentRepository $deploymentRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        DeploymentRepository $deploymentRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
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
        if (!$deployment = $this->deploymentRepo->find($params['id'])) {
            return $notFound();
        }

        $this->entityManager->remove($deployment);
        $this->entityManager->flush();

        $this->session->flash('Deployment removed.', 'success');
        $this->url->redirectFor('repository.deployments', ['repository' => $params['repository']]);
    }
}
