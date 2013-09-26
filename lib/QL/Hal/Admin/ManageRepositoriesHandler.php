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
use QL\Hal\Services\ArrangementService;

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
    private $repoService;

    /**
     * @var ArrangementService
     */
    private $arrService;

    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        RepositoryService $repo,
        ArrangementService $arr
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->repoService = $repo;
        $this->arrService = $arr;
    }

    public function __invoke()
    {
        $arrId = $this->request->post('arrId');
        $shortName = $this->request->post('shortName');
        $githubUser = $this->request->post('githubUser');
        $githubRepo = $this->request->post('githubRepo');
        $ownerEmail = $this->request->post('ownerEmail');
        $buildCommand = $this->request->post('buildCommand');
        $description = $this->request->post('description');
        $errors = [];

        if (!$shortName || !$githubUser || !$githubRepo || !$ownerEmail || !$description) {
            $errors[] = "All fields are required.";
        }
        
        $this->validateShortName($shortName, $errors);        
        $this->validateDescription($description, $errors);

        if ($errors) {
            $data = [
                'errors' => $errors,
                'arrangements' => $this->arrService->listAll(),
                'repositories' => $this->repoService->listAll(),
                'cur_arrid' => $arrId,
                'cur_shortname' => $shortName,
                'cur_githubuser' => $githubUser,
                'cur_githubrepo' => $githubRepo,
                'cur_buildcommand' => $buildCommand,
                'cur_email' => $ownerEmail,
                'cur_description' => $description,
            ];
            $this->response->body($this->tpl->render($data));
            return;
        }

        $this->repoService->create(
            $arrId,
            $shortName,
            $githubUser,
            $githubRepo,
            $buildCommand,
            $ownerEmail,
            $description
        );
        $this->response->status(303);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/repositories';
    }

    private function validateShortName($shortName, array &$errors)
    {
        if (!preg_match('@^[a-zA-Z0-9_-]+$@', $shortName)) {
            $errors[] = "Short Name must consist of alphanumeric, underscore and/or hyphens only.";
        }
        
        if (strlen($shortName) < 2 || strlen($shortName) > 24) {
            $errors[] = "Short Name must be 2 to 24 characters.";
        }
    }

    private function validateDescription($description, array &$errors)
    {
        if (mb_strlen($description, 'UTF-8') > 255 || mb_strlen($description, 'UTF-8') <  2) {
            $errors[] = "Description must be less than 255 characters.";
        }
    }
}
