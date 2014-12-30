<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManager;
use MCP\DataType\HttpUrl;
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
     * @type array|null
     */
    private $invalid;

    /**
     * @type array|null
     */
    private $serverCache;

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

        // store invalid deployments here for error handling
        $this->invalid = [];
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['repository'])) {
            return $notFound();
        }

        $servers = $request->post('server');
        $paths = $request->post('path');
        $urls = $request->post('url'); // optional

        // invalid data, just pop back to page
        if (!$servers || !$paths) {
            $this->url->redirectFor('repository.deployments', ['repository' => $repo->getId()]);
        }

        if (!is_array($servers) || !is_array($paths) || !is_array($urls)) {
            $this->url->redirectFor('repository.deployments', ['repository' => $repo->getId()]);
        }

        if (count($servers) != count($paths) || count($servers) != count($urls)) {
            $this->url->redirectFor('repository.deployments', ['repository' => $repo->getId()]);
        }

        // filter out invalid deployments
        $deployments = $this->filterInvalidDeployments($servers, $paths, $urls);

        // filter out dupes within the submitted data
        $deployments = $this->filterFormDuplicates($deployments);

        // filter out dupes within the stored entities
        $deployments = $this->filterEntityDuplicates($deployments, $repo);

        if ($deployments) {
            foreach ($deployments as $deployment) {
                $this->addDeployment($repo, $deployment[0], $deployment[1], $deployment[2]);
            }

            $this->entityManager->flush();
            $this->session->flash('Deployments added.', 'success');

        } else {
            $this->session->flash('New deployments could not be added.', 'error');
        }

        if ($this->invalid) {
            $this->session->flash($this->buildFlashErrors(), 'warning');
        }

        $this->url->redirectFor('repository.deployments', ['repository' => $repo->getId()]);
    }

    /**
     * @param Repository $repo
     * @param Server $server
     * @param string $path
     * @param HttpUrl $url
     * @return null
     */
    private function addDeployment(Repository $repo, Server $server, $path, HttpUrl $url = null)
    {
        $deployment = new Deployment;

        $deployment->setRepository($repo);
        $deployment->setServer($server);
        $deployment->setPath($path);

        if ($url instanceof HttpUrl) {
            $deployment->setUrl($url);
        }

        $this->entityManager->persist($deployment);
    }

    /**
     * @param string $deployment
     * @param string $reason
     */
    private function addInvalidDeployment($deployment, $reason)
    {
        $this->invalid[] = [
            'deployment' => $deployment,
            'reason' => $reason
        ];
    }

    /**
     * @return string
     */
    private function buildFlashErrors()
    {
        $moreflash = '';
        foreach ($this->invalid as $invalid) {
            switch ($invalid['reason']) {
                case 'duplicate':
                    $reason = 'Duplicate server';
                    break;
                case 'badurl':
                    $reason = 'Invalid url';
                    break;
                default:
                    $reason = 'Invalid path';
                    break;
            }
            $moreflash .= sprintf('<p><strong>%s:</strong> %s</p>', $reason, $invalid['deployment']);
        }

        return <<<HTML
<p>Some deployments were skipped</p>
<div class="alert-bar__details">
$moreflash
</div>
HTML;
    }

    /**
     * @return Server[]
     */
    private function getServers()
    {
        if (!$this->serverCache) {
            $this->serverCache = [];

            foreach ($this->serverRepo->findAll() as $server) {
                $this->serverCache[$server->getId()] = $server;
            }
        }

        return $this->serverCache;
    }

    /**
     * Return valid and unique entries for server:path
     *
     * @param array $servers
     * @param array $paths
     *
     * @return array
     */
    private function filterInvalidDeployments(array $servers, array $paths, array $urls)
    {
        $deployments = [];
        $serverCache = $this->getServers();

        foreach ($servers as $entry => $serverId) {
            // skip invalid server
            if (!$serverId || !isset($serverCache[$serverId])) {
                continue;
            }

            // skip invalid paths
            if (!isset($paths[$entry]) || substr($paths[$entry], 0, 1) !== '/') {
                $this->addInvalidDeployment(sprintf('%s:%s', $serverCache[$serverId]->getName(), $paths[$entry]), 'invalid');
                continue;
            }

            if (strlen($urls[$entry]) > 0) {
                $url = HttpUrl::create($urls[$entry]);

                // skip invalid urls
                if (!$url instanceof HttpUrl) {
                    $this->addInvalidDeployment(sprintf('%s:%s', $serverCache[$serverId]->getName(), $paths[$entry]), 'badurl');
                    continue;
                }
            } else {
                $url = null;
            }

            $deployments[] = [$serverId, $paths[$entry], $url];
        }

        return $deployments;
    }

    /**
     * @param array $deployments
     *
     * @return array
     */
    private function filterFormDuplicates(array $deployments)
    {
        $uniqueDeployments = [];
        $dupes = [];
        $serverCache = $this->getServers();

        foreach ($deployments as $deployment) {
            $server = $serverCache[$deployment[0]];
            $hash = sprintf('%s:%s', $server->getName(), $deployment[1]);

            if (!isset($dupes[$hash])) {
                $uniqueDeployments[] = $deployment;
                $dupes[$hash] = true;
            } else {
                $this->addInvalidDeployment($hash, 'duplicate');
            }
        }

        return $uniqueDeployments;
    }

    /**
     * @param array $deployments
     * @param Repository $repository
     *
     * @return array
     */
    private function filterEntityDuplicates(array $deployments, Repository $repository)
    {
        $uniqueDeployments = [];
        $serverCache = $this->getServers();

        foreach ($deployments as $deployment) {
            $server = $serverCache[$deployment[0]];
            $path = $deployment[1];
            $url = $deployment[2];

            $hash = sprintf('%s:%s', $server->getName(), $path);

            if ($this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path])) {
                $this->addInvalidDeployment($hash, 'duplicate');

            } else {
                $uniqueDeployments[] = [$server, $path, $url];
            }
        }

        return $uniqueDeployments;
    }
}
