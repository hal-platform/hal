<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\GitBert2;

use QL\GitBert2\Services\RepositoryService;
use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;

/**
 * @api
 */
class GBRepositoriesHandler
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
    private $repos;

    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        RepositoryService $repos
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->repos = $repos;
    }

    public function __invoke()
    {
        $githubuser = $this->request->post('githubuser');
        $githubrepo = $this->request->post('githubrepo');
        $email = $this->request->post('email');
        $shortname = $this->request->post('shortname');
        $description = $this->request->post('description');
        

        if (!$githubuser || !$githubrepo || !$email || !$shortname || !$description) {
            $this->response->body($this->tpl->render(['error' => "all fields are required"]));
            return;
        }

        $this->repos->create($shortname, $githubuser, $githubrepo, $email, $description);
        $this->response->status(302);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/repositories';
    }
}
