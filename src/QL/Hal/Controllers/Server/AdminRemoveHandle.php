<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminRemoveHandle
{
    /**
     * @var ServerRepository
     */
    private $serverRepo;

    /**
     * @var DeploymentRepository
     */
    private $deployRepo;

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
     * @param ServerRepository $serverRepo
     * @param DeploymentRepository $deployRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        ServerRepository $serverRepo,
        DeploymentRepository $deployRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->serverRepo = $serverRepo;
        $this->deployRepo = $deployRepo;
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
        if (!$server = $this->serverRepo->find($params['id'])) {
            return $notFound();
        }

        if ($deployments = $this->deployRepo->findBy(['server' => $server])) {
            $this->session->addFlash('Cannot remove server. All associated deployments must first be removed.', 'server-remove');
            return $this->url->redirectFor('server', ['id' => $params['id']]);
        }

        $this->entityManager->remove($server);
        $this->entityManager->flush();

        $message = sprintf('Server "%s" removed.', $server->getName());
        $this->session->addFlash($message, 'server-remove');
        $this->url->redirectFor('servers');
    }
}
