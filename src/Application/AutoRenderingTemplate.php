<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application;

use QL\Panthor\Twig\LazyTwig;
use Slim\Http\Response;

class AutoRenderingTemplate extends LazyTwig
{
    /**
     * @var Response
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
