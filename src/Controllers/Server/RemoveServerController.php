<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveServerController implements ControllerInterface
{
    /**
     * @type EntityRepository
     */
    private $serverRepo;
    private $deployRepo;

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
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->deployRepo = $em->getRepository(Deployment::CLASS);
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
        if (!$server = $this->serverRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($deployments = $this->deployRepo->findBy(['server' => $server])) {
            $this->session->flash('Cannot remove server. All associated deployments must first be removed.', 'error');
            return $this->url->redirectFor('server', ['id' => $this->parameters['id']]);
        }

        $this->em->remove($server);
        $this->em->flush();

        $name = $server->name();
        if ($server->type() === ServerEnum::TYPE_EB) {
            $name = 'Elastic Beanstalk';
        } elseif ($server->type() === ServerEnum::TYPE_EC2) {
            $name = 'EC2';
        }

        $message = sprintf('Server "%s" removed.', $name);
        $this->session->flash($message, 'success');
        $this->url->redirectFor('servers');
    }
}
