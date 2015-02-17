<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AddEncryptedPropertyController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

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
     *
     * @param RepositoryRepository $repoRepo
     * @param EnvironmentRepository $envRepo
     *
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        EnvironmentRepository $envRepo,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
        $this->envRepo = $envRepo;

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

        if (!$environments = $this->envRepo->findBy([], ['order' => 'ASC'])) {
            // who cares. I dont really want to deal with redirecting
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'form' => [
                'environment' => $this->request->post('environment'),
                'name' => $this->request->post('name'),
                'decrypted' => $this->request->post('decrypted')
            ],
            'repository' => $repo,
            'environments' => $environments
        ]);

        $this->response->setBody($rendered);
    }
}
