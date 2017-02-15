<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

/**
 * Render a twig template and do nothing else.
 */
class StaticController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @param TemplateInterface $template
     * @param Response $response
     * @param int $statusCode
     */
    public function __construct(TemplateInterface $template, Response $response, $statusCode = 200)
    {
        $this->template = $template;
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $rendered = $this->template->render();

        $this->response->setStatus($this->statusCode);
        $this->response->setBody($rendered);
    }
}
