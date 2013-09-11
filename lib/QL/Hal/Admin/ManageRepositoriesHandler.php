<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;
use QL\Hal\Services\RepositoryService;

/**
 * @api
 */
class ManageRepositoriesHandler
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var RepositoryService
     */
    private $repo;

    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        RepositoryService $repo
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->repoService = $repo;
    }

    public function __invoke()
    {
        $arrId = $this->request->post('arrId');
        $shortName = $this->request->post('shortName');
        $githubUser = $this->request->post('githubUser');
        $githubRepo = $this->request->post('githubRepo');
        $ownerEmail = $this->request->post('ownerEmail');
        $description = $this->request->post('description');
        

        if (!$shortName || !$githubUser || !$githubRepo || !$ownerEmail || !$description) {
            $this->response->body($this->tpl->render(['error' => "all fields are required"]));
            return;
        }

        $this->repoService->create($arrId, $shortName, $githubUser, $githubRepo, $ownerEmail, $description);
        $this->response->status(302);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/repositories';
    }
}
