<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Psr\Http\Message\ResponseInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\HTTP\NewBodyTrait;

/**
 * Render a template and do nothing else.
 */
class StaticController implements ControllerInterface
{
    use NewBodyTrait;
    use ResponseOnlyControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @param TemplateInterface $template
     * @param int $statusCode
     */
    public function __construct(TemplateInterface $template, int $statusCode = 200)
    {
        $this->template = $template;
        $this->statusCode = $statusCode;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function execute(ResponseInterface $response): ResponseInterface
    {
        $rendered = $this->template->render();
        return $this
            ->withNewBody($response, $rendered)
            ->withStatus($this->statusCode);
    }
}
