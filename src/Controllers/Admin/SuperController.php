<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\ORM\Configuration;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class SuperController
{
    /**
     * @type Twig_Template
     */
    private $template;

    /**
     * @type Configuration
     */
    private $doctrineConfig;

    /**
     * @type string
     */
    private $halPushFile;

    /**
     * @param Twig_Template $template
     * @param Configuration $doctrineConfig
     * @param string $halPushFile
     */
    public function __construct(Twig_Template $template, Configuration $doctrineConfig, $halPushFile)
    {
        $this->template = $template;
        $this->doctrineConfig = $doctrineConfig;
        $this->halPushFile = $halPushFile;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $context = [
            'servername' => gethostname()
        ];

        # add hal push file if possible.
        if (file_exists($this->halPushFile)) {
            $context['pushfile'] = file_get_contents($this->halPushFile);
        }

        # clear doctrine
        if ($request->get('clear_doctrine')) {
            $context['doctrine_status'] = [
                'Query' => $this->clearDoctrine('getQueryCacheImpl'),
                'Hydration' => $this->clearDoctrine('getHydrationCacheImpl'),
                'Metadata' => $this->clearDoctrine('getMetadataCacheImpl')
            ];
        }

        # clear permissions
        if ($request->get('clear_permissions')) {

        }

        $rendered = $this->template->render($context);

        $response->setBody($rendered);
    }

    /**
     * @param string $accessor
     *
     * @return string
     */
    private function clearDoctrine($accessor)
    {
        if (!$cache = $this->doctrineConfig->$accessor()) {
            return 'Cache missing.';
        }

        $cache->deleteAll();
        return sprintf('"%s" reset.', get_class($cache));
    }
}
