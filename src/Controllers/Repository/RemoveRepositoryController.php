<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveRepositoryController implements ControllerInterface
{
    const SUCCESS = 'Repository "%s" removed.';
    const ERR_HAS_DEPLOYMENTS = 'Cannot remove application. All server deployments must first be removed.';

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $deploymentRepo;

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
     */
    public function __construct(
        EntityManagerInterface $em,
        Session $session,
        UrlHelper $url,
        NotFound $notFound,
        array $parameters
    ) {
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
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
        if (!$application = $this->applicationRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($deployments = $this->deploymentRepo->findBy(['application' => $application])) {
            $this->session->flash(self::ERR_HAS_DEPLOYMENTS, 'error');
            return $this->url->redirectFor('repository'. ['id' => $repo->id()]);
        }

        $this->em->remove($application);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $application->key());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('repositories');
    }
}
