<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use QL\Panthor\MiddlewareInterface;
use Slim\Http\Request;

class AddDeploymentHandler implements MiddlewareInterface
{
    /**
     * @type Request
     */
    private $request;

    /**
     * @type AddDeploymentFormHandler
     */
    private $formHandler;

    /**
     * @type AddDeploymentJsonHandler
     */
    private $jsonHandler;

    /**
     * @param Request $request
     * @param AddDeploymentFormHandler $formHandler
     * @param AddDeploymentJsonHandler $jsonHandler
     */
    public function __construct(
        Request $request,
        AddDeploymentFormHandler $formHandler,
        AddDeploymentJsonHandler $jsonHandler
    ) {
        $this->request = $request;
        $this->formHandler = $formHandler;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $isAjax = ($this->request->getMediaType() === 'application/json');

        // default to form handler
        $handler = $this->formHandler;
        if ($isAjax) {
            $handler = $this->jsonHandler;
        }

        // delegate to actual handler
        call_user_func_array($handler, func_get_args());
    }
}
