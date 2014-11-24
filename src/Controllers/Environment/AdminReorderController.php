<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminReorderController
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
     * @param TemplateInterface $template
     * @param EnvironmentRepository $envRepo
     */
    public function __construct(TemplateInterface $template, EnvironmentRepository $envRepo)
    {
        $this->template = $template;
        $this->envRepo = $envRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$environments = $this->envRepo->findBy([], ['order' => 'ASC'])) {
            return $notFound();
        }

        $rendered = $this->template->render([
            'envs' => $environments
        ]);

        $response->setBody($rendered);
    }
}
