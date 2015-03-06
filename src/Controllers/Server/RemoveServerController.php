<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\ServerRepository;
use QL\Hal\Core\Entity\Type\ServerEnumType;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveServerController implements ControllerInterface
{
    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type DeploymentRepository
     */
    private $deployRepo;

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
     * @param ServerRepository $serverRepo
     * @param DeploymentRepository $deployRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        ServerRepository $serverRepo,
        DeploymentRepository $deployRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        NotFound $notFound,
        array $parameters
    ) {
        $this->serverRepo = $serverRepo;
        $this->deployRepo = $deployRepo;
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
        if (!$server = $this->serverRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($deployments = $this->deployRepo->findBy(['server' => $server])) {
            $this->session->flash('Cannot remove server. All associated deployments must first be removed.', 'error');
            return $this->url->redirectFor('server', ['id' => $this->parameters['id']]);
        }

        $this->entityManager->remove($server);
        $this->entityManager->flush();

        $name = $server->getName();
        if ($server->getType() === ServerEnumType::TYPE_EB) {
            $name = 'Elastic Beanstalk';
        } elseif ($server->getType() === ServerEnumType::TYPE_EC2) {
            $name = 'EC2';
        }

        $message = sprintf('Server "%s" removed.', $name);
        $this->session->flash($message, 'success');
        $this->url->redirectFor('servers');
    }
}
