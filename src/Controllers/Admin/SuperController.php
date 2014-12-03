<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class SuperController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type string
     */
    private $encryptionKey;

    /**
     * @type string
     */
    private $sessionEncryptionKey;

    /**
     * @type string
     */
    private $halPushFile;

    /**
     * @param TemplateInterface $template
     * @param string $encryptionKey
     * @param string $sessionEncryptionKey
     * @param string $halPushFile
     */
    public function __construct(
        TemplateInterface $template,
        $encryptionKey,
        $sessionEncryptionKey,
        $halPushFile
    ) {
        $this->template = $template;

        $this->encryptionKey = $encryptionKey;
        $this->sessionEncryptionKey = $sessionEncryptionKey;
        $this->halPushFile = $halPushFile;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $context = [
            'servername' => gethostname(),
            'encryption_key' => $this->encryptionKey,
            'session_encryption_key' => $this->sessionEncryptionKey,
            'freespace' => $this->getFreespace()
        ];

        # add hal push file if possible.
        if (file_exists($this->halPushFile)) {
            $context['pushfile'] = file_get_contents($this->halPushFile);
        }

        $rendered = $this->template->render($context);

        $response->setBody($rendered);
    }

    /**
     * @return string
     */
    private function getFreespace()
    {
        exec('df -a', $output);

        return implode("\n", $output);
    }
}
