<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Repository\ServerRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveEnvironmentHandler implements ControllerInterface
{
    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

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
     * @param EnvironmentRepository $envRepo
     * @param ServerRepository $serverRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EnvironmentRepository $envRepo,
        ServerRepository $serverRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        NotFound $notFound,
        array $parameters
    ) {
        $this->envRepo = $envRepo;
        $this->serverRepo = $serverRepo;
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
        if (!$environment = $this->envRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($servers = $this->serverRepo->findBy(['environment' => $environment])) {
            $this->session->flash('Cannot remove environment. All associated servers must first be removed.', 'error');
            return $this->url->redirectFor('environment', ['id' => $this->parameters['id']]);
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
