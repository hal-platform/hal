<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use QL\Panthor\MiddlewareInterface;
use Slim\Http\Request;

class AddDeploymentHandler implements MiddlewareInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var AddDeploymentFormHandler
     */
    private $formHandler;

    /**
     * @var AddDeploymentJsonHandler
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
