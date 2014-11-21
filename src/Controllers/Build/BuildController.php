<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class BuildController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @param TemplateInterface $template
     * @param BuildRepository $buildRepo
     */
    public function __construct(TemplateInterface $template, BuildRepository $buildRepo)
    {
        $this->template = $template;
        $this->buildRepo = $buildRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['build']]);

        if (!$build) {
            return call_user_func($notFound);
        }

        $rendered = $this->template->render([
            'build' => $build
        ]);

        $response->setBody($rendered);
    }
}
