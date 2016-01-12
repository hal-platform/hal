<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
    const SUCCESS = 'Deployment "%s" removed.';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DeploymentPool
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

        $name = $this->deployment->server()->formatPretty();

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
