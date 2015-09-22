<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Flasher;
use QL\Panthor\MiddlewareInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RemoveHandler implements MiddlewareInterface
{
    use ServerFormatterTrait;

    const SUCCESS = 'Deployment "%s" removed.';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type DeploymentPool
     */
    private $pool;

    /**
     * @param Request $request
     * @param Response $response
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param DeploymentPool $pool
     * @param Deployment $deployment
     */
    public function __construct(
        Request $request,
        Response $response,
        Flasher $flasher,
        EntityManagerInterface $em,
        DeploymentPool $pool,
        Deployment $deployment
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->flasher = $flasher;

        $this->em = $em;

        $this->pool = $pool;
        $this->deployment = $deployment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->removeDeploymentRelation();

        $isAjax = ($this->request->getMediaType() === 'application/json');

        if ($isAjax) {
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->setBody('{}');
            return;
        }

        $name = $this->formatServerType($this->deployment->server());

        $message = sprintf(self::SUCCESS, $name);
        $this->flasher
            ->withFlash($message, 'success')
            ->load('deployment_view', ['view' => $this->pool->view()->id()]);
    }

    /**
     * @return void
     */
    private function removeDeploymentRelation()
    {
        $this->pool->deployments()->removeElement($this->deployment);

        $this->em->merge($this->pool);
        $this->em->flush();
    }
}
