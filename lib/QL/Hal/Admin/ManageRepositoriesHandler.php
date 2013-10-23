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
        Twig_Template $tpl,
        RepositoryService $repo,
        ArrangementService $arr
    ) {
        $this->tpl = $tpl;
        $this->repoService = $repo;
        $this->arrService = $arr;
    }

    public function __invoke(Request $req, Response $res)
    {
        $arrId = $req->post('arrId');
        $shortName = $req->post('shortName');
        $githubUser = $req->post('githubUser');
        $githubRepo = $req->post('githubRepo');
        $ownerEmail = $req->post('ownerEmail');
        $buildCommand = $req->post('buildCommand');
        $description = $req->post('description');
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
            $res->body($this->tpl->render($data));
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
        $res->status(303);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/admin/repositories');
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
