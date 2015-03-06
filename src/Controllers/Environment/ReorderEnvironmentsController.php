<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class ReorderEnvironmentsController implements ControllerInterface
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
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param EnvironmentRepository $envRepo
     * @param Response $response
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        EnvironmentRepository $envRepo,
        Response $response,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->envRepo = $envRepo;

        $this->response = $response;
        $this->notFound = $notFound;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$environments = $this->envRepo->findBy([], ['order' => 'ASC'])) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'envs' => $environments
        ]);

        $this->response->setBody($rendered);
    }
}
