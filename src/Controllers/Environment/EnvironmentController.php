<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class EnvironmentController
{
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
     * @param TemplateInterface $template
     * @param EnvironmentRepository $envRepo
     * @param ServerRepository $serverRepo
     */
    public function __construct(
        TemplateInterface $template,
        EnvironmentRepository $envRepo,
        ServerRepository $serverRepo
    ) {
        $this->template = $template;
        $this->envRepo = $envRepo;
        $this->serverRepo = $serverRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$environment = $this->envRepo->find($params['id'])) {
            return $notFound();
        }

        $rendered = $this->template->render([
            'env' => $environment,
            'servers' => $this->serverRepo->findBy(['environment' => $environment])
        ]);

        $response->setBody($rendered);
    }
}
