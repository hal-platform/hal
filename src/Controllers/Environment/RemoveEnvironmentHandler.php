<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveEnvironmentHandler implements ControllerInterface
{
    /**
     * @type EntityRepository
     */
    private $envRepo;
    private $serverRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

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
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param UrlHelper $url
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        Session $session,
        UrlHelper $url,
        NotFound $notFound,
        array $parameters
    ) {
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->em = $em;

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

        $this->em->remove($environment);
        $this->em->flush();

        $message = sprintf('Environment "%s" removed.', $environment->getKey());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('environments');
    }
}
