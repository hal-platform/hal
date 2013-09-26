<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ArrangementService;

/**
 * @api
 */
class Repositories
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var RepositoryService
     */
    private $repoService;

    /**
     * @var ArrangementService
     */
    private $arrService;


    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param ArrangementService $arrService
     */
    public function __construct(Response $response, Twig_Template $tpl, RepositoryService $repoService, ArrangementService $arrService)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->arrService = $arrService;
    }

    /**
     * @param int $commonId
     * @param Slim $app
     * @return null
     */
    public function __invoke()
    {
        $repo = $this->repoService->getById($commonId);
        if (is_null($user)) {
            $app->notFound();
            return;
        }
        $this->response->body($this->tpl->render([
            'user' => $user,
            'total_pushes' => 0,
        ]));
    }
}
