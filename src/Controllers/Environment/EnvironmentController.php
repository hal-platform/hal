<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class EnvironmentController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

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
     * @param EnvironmentRepository $envRepo
     * @param ServerRepository $serverRepo
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EnvironmentRepository $envRepo,
        ServerRepository $serverRepo,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->envRepo = $envRepo;
        $this->serverRepo = $serverRepo;

        $this->response = $response;
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

        $servers = $this->serverRepo->findBy(['environment' => $environment]);
        usort($servers, $this->serverSorter());

        $rendered = $this->template->render([
            'env' => $environment,
            'servers' => $servers
        ]);

        $this->response->setBody($rendered);
    }
}
