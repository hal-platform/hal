<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Hal\Services\SyncOptions;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class SyncPage
{
    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var SyncOptions
     */
    private $syncOptions;

    /**
     * @param Twig_Template $tpl
     * @param SyncOptions $syncOptions
     */
    public function __construct(
        Twig_Template $tpl,
        SyncOptions $syncOptions
    ) {
        $this->tpl = $tpl;
        $this->syncOptions = $syncOptions;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array|null $params
     * @param callable|null $notFound
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $repoShortName = $params['name'];
        $deps = $req->get('deps');

        if (!is_array($deps)) {
            $deps = [];
        }

        $options = $this->syncOptions->syncOptionsByRepoShortName($repoShortName, $deps);

        if (!isset($options['repo'])) {
            call_user_func($notFound);
            return;
        }

        if (!$options['deps']) {
            $res->setBody($this->tpl->render($options));
            return;
        }

        $options['toolong'] = false;
        if (count($options['branches']) > 100) {
            $options['toolong'] = 100;
        }

        $res->setBody($this->tpl->render($options));
    }
}
