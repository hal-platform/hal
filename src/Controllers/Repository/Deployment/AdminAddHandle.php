<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminAddHandle
{
    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

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
     * @param ServerRepository $serverRepo
     * @param RepositoryRepository $repoRepo
     * @param DeploymentRepository $deploymentRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        ServerRepository $serverRepo,
        RepositoryRepository $repoRepo,
        DeploymentRepository $deploymentRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->serverRepo = $serverRepo;
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

        $servers = $request->post('server');
        $paths = $request->post('path');

        // invalid data, just pop back to page
        if (!$servers || !$paths) {
            $this->url->redirectFor('repository.deployments', ['id' => $repo->getId()]);
        }

        if (!is_array($servers) || !is_array($paths)) {
            $this->url->redirectFor('repository.deployments', ['id' => $repo->getId()]);
        }

        $serverCache = $this->getServerCache();

        if ($deployments = $this->sanitizeDeployments($servers, $paths, $serverCache)) {
            foreach ($deployments as $deployment) {
                $this->addDeployment($repo, $deployment[0], $deployment[1]);
            }

            $this->entityManager->flush();
            $flash = 'Deployments added.';

        } else {
            // should have better fault tolerance // error handling
            $flash = 'New deployments could not be added.';
        }

        $this->session->addFlash($flash, 'deployment-add');

        $this->url->redirectFor('repository.deployments', ['id' => $repo->getId()]);
    }

    /**
     * @param Repository $repo
     * @param Server $server
     * @param string $path
     * @return null
     */
    private function addDeployment(Repository $repo, Server $server, $path)
    {
        $deployment = new Deployment;

        $deployment->setRepository($repo);
        $deployment->setServer($server);
        $deployment->setPath($path);

        $this->entityManager->persist($deployment);
    }

    /**
     * @return Server[]
     */
    private function getServerCache()
    {
        $servers = [];
        $serverEntities = $this->serverRepo->findAll();

        foreach ($serverEntities as $server) {
            $servers[$server->getId()] = $server;
        }

        return $servers;
    }

    /**
     * Return valid and unique entries for server:path
     *
     * @param array $servers
     * @param array $paths
     * @param array $serverCache
     * @return array
     */
    private function sanitizeDeployments(array $servers, array $paths, array $serverCache)
    {
        $deployments = [];
        foreach ($servers as $entry => $server) {
            // skip invalid server
            if (!$server || !isset($serverCache[$server])) {
                continue;
            }

            // skip invalid paths
            if (!isset($paths[$entry]) || substr($paths[$entry], 0, 1) !== '/') {
                continue;
            }

            $deployments[] = [$server, $paths[$entry]];
        }

        // filter out dupes within the submitted data
        $deployments = $this->filterFormDuplicates($deployments);

        // filter out dupes within the stored entities
        $deployments = $this->filterEntityDuplicates($deployments, $serverCache);

        return $deployments;
    }

    /**
     * @param array $deployments
     * @return array
     */
    private function filterFormDuplicates(array $deployments)
    {
        $uniqueDeployments = [];
        $dupes = [];

        foreach ($deployments as $deployment) {
            $hash = sprintf('%s:%s', $deployment[0], $deployment[1]);

            if (!isset($dupes[$hash])) {
                $uniqueDeployments[] = $deployment;
                $dupes[$hash] = true;
            }
        }

        return $uniqueDeployments;
    }

    /**
     * @param array $deployments
     * @param array $serverCache
     * @return array
     */
    private function filterEntityDuplicates(array $deployments, array $serverCache)
    {
        $uniqueDeployments = [];

        foreach ($deployments as $deployment) {
            $server = $serverCache[$deployment[0]];
            $path = $deployment[1];

            if (!$this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path])) {
                $uniqueDeployments[] = [$server, $path];
            }
        }

        return $uniqueDeployments;
    }
}
