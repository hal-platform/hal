<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api;

use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\Url;

class DocsRedirectController implements ControllerInterface
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @param Url $url
     */
    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->url->redirectForURL('/docs/api/index.html', [], 301);
    }
}
