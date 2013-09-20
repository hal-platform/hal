<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use QL\Hal\Services\ServerService;
use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;

/**
 * @api
 */
class ManageServersHandler
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
     * @var ServerService
     */
    private $servers;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Response $response
     * @param Request $request
     * @param Twig_Template $tpl
     * @param ServerService $servers
     * @param EnvironmentService $envService
     */
    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        ServerService $servers,
        EnvironmentService $envService
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->servers = $servers;
        $this->envService = $envService;
    }

    public function __invoke()
    {
        $hostname = $this->request->post('hostname');
        $envId = $this->request->post('envId');
        $errors = [];

        $this->validateHostName($hostname, $errors);
        $this->validateEnvId($envId, $errors);

        if ($errors) {
            $data = [
                'errors' => $errors,
                'envs' => $this->envService->listAll(),
                'servers' => $this->servers->listAll(),
                'selectedEnv' => $envId,
                'serverVal' => $hostname,
            ];
            $this->response->body($this->tpl->render($data));
            return;
        }

        $this->servers->create(strtolower($hostname), $envId);
        $this->response->status(303);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/servers';
    }

    /**
     * Validates if a given string is a valid domain name according to RFC 1034
     *
     * The one exception to the spec is a domain name may start with a number.
     * In reality I know this is allowed, but I can't find any mention in any
     * other RFC.
     *
     * Additionally this validates the app specific length requirements.
     *
     * Examples:
     * - www.example.com - good
     * - .example.com - bad
     * - www..example.com - bad
     * - 1-800-flowers.com - good
     * - -awesome-.com - bad
     * - x---x.ql - good
     *
     * @param string $hostname
     * @param string[] $errors
     * @return null
     */
    private function validateHostName($hostname, array &$errors)
    {
        $regex = '@^([0-9a-z]([0-9a-z-]*[0-9a-z])?)(\.[0-9a-z]([0-9a-z-]*[0-9a-z])?)*$@';
        if (!preg_match($regex, $hostname)) {
            $errors[] = 'Hostname must only use numbers, letters, hyphens and periods.';
        }
        if (strlen($hostname) > 24) {
            $errors[] = "Hostname must be less than or equal to 32 characters";
        }
        if (strlen($hostname) === 0) {
            $errors[] = "You must enter a hostname";
        }
    }

    /**
     * @param int $envId
     * @param string[] $errors
     * @return null
     */
    private function validateEnvId($envId, array &$errors)
    {
        if (!$this->envService->getById($envId)) {
            $errors[] = 'EnvId must be valid';
        }
    }
}
