<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use QL\Panthor\Twig\LazyTwig;
use Slim\Http\Response;

class AutoRenderingTemplate extends LazyTwig
{
    /**
     * @type Response
     */
    private $response;

    /**
     * @param Response $response
     *
     * @return null
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Render the template with context data and automatically add to response
     *
     * @param array $context
     *
     * @return string
     */
    public function render(array $context = [])
    {
        $rendered = parent::render($context);

        if ($this->response) {
            $this->response->setBody($rendered);
        }

        return $rendered;
    }
}
