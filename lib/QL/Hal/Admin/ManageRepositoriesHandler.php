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
    private $repo;

    /**
     * @var ArrangementService
     */
    private $arr;

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
        $description = $this->request->post('description');
        $errors = [];

        if (!$shortName || !$githubUser || !$githubRepo || !$ownerEmail || !$description) {
            $errors[] = "All fields are required.";
        }
        
        $this->validateShortName($shortName, $errors);        
        $this->validateDescription($description, $errors);

        if($errors) {
            $this->response->body($this->tpl->render([
                'errors' => $errors,
                'arrangements' => $this->arrService->listAll(), 
                'repositories' => $this->repoService->listAll()]));
            return;
        } else{
            $this->repoService->create($arrId, $shortName, $githubUser, $githubRepo, $ownerEmail, $description);
            $this->response->status(302);
            $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/repositories';
        }
    }

    private function validateShortName($shortName, array &$errors)
    {
        if (!preg_match('@^[a-zA-Z0-9]+$@', $shortName)) {
            $errors[] = "Short Name must be alphanumeric only";
        }
        
        if (mb_strlen($shortName) < 2 || mb_strlen($shortName) > 16) {
            $errors[] = "Short Name must be 2 to 16 characters.";
        }
    }

    private function validateDescription($description, array &$errors)
    {
        if (mb_strlen($description) > 255 || mb_strlen($description) <  2) {
            $errors[] = "Description must be less than 255 characters.";
        }
    }
}
