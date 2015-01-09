<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class EnvironmentsController implements ControllerInterface
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
     * @param TemplateInterface $template
     * @param EnvironmentRepository $envRepo
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, EnvironmentRepository $envRepo, Response $response)
    {
        $this->template = $template;
        $this->envRepo = $envRepo;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $rendered = $this->template->render([
            'envs' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ]);

        $this->response->setBody($rendered);
    }
}
