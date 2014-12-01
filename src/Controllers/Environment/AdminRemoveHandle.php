<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Session;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminRemoveHandle
{
    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

    /**
     * @var ServerRepository
     */
    private $serverRepo;

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
     * @param EnvironmentRepository $envRepo
     * @param ServerRepository $serverRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        EnvironmentRepository $envRepo,
        ServerRepository $serverRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->envRepo = $envRepo;
        $this->serverRepo = $serverRepo;
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
        if (!$environment = $this->envRepo->find($params['id'])) {
            return $notFound();
        }

        if ($servers = $this->serverRepo->findBy(['environment' => $environment])) {
            $this->session->flash('Cannot remove environment. All associated servers must first be removed.', 'error');
            return $this->url->redirectFor('environment', ['id' => $params['id']]);
        }

        $this->entityManager->remove($environment);
        $this->reorderEnvironments($environment);
        $this->entityManager->flush();

        $message = sprintf('Environment "%s" removed.', $environment->getKey());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('environments');
    }

    /**
     * @param Environment $removedEnv
     * @return null
     */
    private function reorderEnvironments(Environment $removedEnv)
    {
        $order = 1;
        $envs = $this->envRepo->findBy([], ['order' => 'ASC']);

        foreach ($envs as $env) {
            // skip the env being removed
            if ($env->getId() === $removedEnv->getId()) {
                continue;
            }

            $env->setOrder($order++);
            $this->entityManager->merge($env);
        }
    }
}
