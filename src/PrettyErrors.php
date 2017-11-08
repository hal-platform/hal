<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI;

class PrettyErrors
{
    // Add docs
    private $app;
    private $request;
    private $handler;
    private $runner;

    public function __construct($app, $request)
    {
        $this->app = $app;
        $this->request = $request;

        // Should probably inject these
        $this->handler = new \Whoops\Handler\PrettyPageHandler;
        $this->runner = new \Whoops\Run();
    }

    public function register()
    {
        $this->handler->addDataTable('Application', array(
            'Application Class' => get_class($this->app)
        ));

        $this->handler->addDataTable('Application (Request)', array(
            'Path Info'   => $this->request->getUri()->getPath(),
            'Query String' => $this->request->getUri()->getQuery() ?: '<none>',
            'HTTP Method' => $this->request->getMethod(),
            'Base URL'    => (string) $this->request->getUri(),
            'Scheme'      => $this->request->getUri()->getScheme(),
            'Port'        => $this->request->getUri()->getPort(),
            'Host'        => $this->request->getUri()->getHost(),
        ));

        $this->runner->allowQuit(false);
        $this->runner->pushHandler($this->handler);
        $this->runner->register();
    }

    public function registerShutdown()
    {
    }
}
