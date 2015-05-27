<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class EditDeploymentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $repoRepo;
    private $deploymentRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->repoRepo = $em->getRepository(Repository::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->request = $request;
        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$repo = $this->repoRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        if (!$deployment = $this->deploymentRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $url = $deployment->getUrl() ? $deployment->getUrl()->asString() : '';
        $renderContext = [
            'form' => [
                'path' => $this->request->isPost() ? $this->request->post('path') : $deployment->getPath(),
                'eb_environment' => $this->request->isPost() ? $this->request->post('eb_environment') : $deployment->getEbEnvironment(),
                'ec2_pool' => $this->request->isPost() ? $this->request->post('ec2_pool') : $deployment->getEc2Pool(),
                'url' => $this->request->isPost() ? $this->request->post('url') : $url,
            ],
            'deployment' => $deployment
        ];

        $rendered = $this->template->render($renderContext);
        $this->response->setBody($rendered);
    }
}
