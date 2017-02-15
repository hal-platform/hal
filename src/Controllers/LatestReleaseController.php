<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class LatestReleaseController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @param TemplateInterface $template
     */
    public function __construct(TemplateInterface $template)
    {
        $this->template = $template;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $this->template->render([
            'release_notes' => ''
        ]);
    }
}
