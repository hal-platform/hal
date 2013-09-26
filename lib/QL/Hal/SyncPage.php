<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class SyncPage
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var SyncOptions
     */
    private $syncOptions;

    /**
     * @param Request $request
     * @param Response $response
     * @param Twig_Template $tpl
     * @param SyncOptions $syncOptions
     */
    public function __construct(
        Request $request,
        Response $response,
        Twig_Template $tpl,
        SyncOptions $syncOptions
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->tpl = $tpl;
        $this->syncOptions = $syncOptions;
    }

    /**
     * @param string $repoShortName
     * @param callable $notFound
     */
    public function __invoke($repoShortName, callable $notFound)
    {
        $deps = $this->request->get('deps');

        if (!is_array($deps)) {
            $deps = [];
        }

        $options = $this->syncOptions->syncOptionsByRepoShortName($repoShortName, $deps);

        if (!isset($options['repo'])) {
            call_user_func($notFound);
            return;
        }

        if (!$options['deps']) {
            $this->response->setBody($this->tpl->render($options));
            return;
        }

        $options['toolong'] = false;
        if (count($options['branches']) > 100) {
            $options['toolong'] = 100;
        }

        $this->response->setBody($this->tpl->render($options));
    }
}
