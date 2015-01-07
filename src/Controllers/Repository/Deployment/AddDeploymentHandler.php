<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Slim\Http\Request;
use Slim\Http\Response;

class AddDeploymentHandler
{
    /**
     * @type AddDeploymentFormHandler
     */
    private $formHandler;

    /**
     * @type AddDeploymentJsonHandler
     */
    private $jsonHandler;

    /**
     * @param AddDeploymentFormHandler $formHandler
     * @param AddDeploymentJsonHandler $jsonHandler
     */
    public function __construct(AddDeploymentFormHandler $formHandler, AddDeploymentJsonHandler $jsonHandler)
    {
        $this->formHandler = $formHandler;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, $params = [])
    {
        if (!$request->isPost()) {
            return;
        }

        $isAjax = ($request->getMediaType() === 'application/json');

        // default to form handler
        $handler = $this->formHandler;
        if ($isAjax) {
            $handler = $this->jsonHandler;
        }

        // delegate to actual handler
        call_user_func_array($handler, func_get_args());
    }
}
