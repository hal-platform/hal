<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Entity\Repository\ServerRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class ServersController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @param Twig_Template $template
     *  @param ServerRepository $serverRepo
     */
    public function __construct(Twig_Template $template, ServerRepository $serverRepo)
    {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $rendered = $this->template->render([
            'servers' => $this->serverRepo->findBy([], ['environment' => 'ASC', 'name' => 'ASC'])
        ]);

        $response->setBody($rendered);
    }
}
