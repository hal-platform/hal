<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Super;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class SuperController implements ControllerInterface
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
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param Response $response
     * @param string $encryptionKey
     * @param string $sessionEncryptionKey
     * @param string $halPushFile
     */
    public function __construct(
        TemplateInterface $template,
        Response $response,
        $encryptionKey,
        $sessionEncryptionKey,
        $halPushFile
    ) {
        $this->template = $template;
        $this->response = $response;

        $this->encryptionKey = $encryptionKey;
        $this->sessionEncryptionKey = $sessionEncryptionKey;
        $this->halPushFile = $halPushFile;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
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

        $this->response->setBody($rendered);
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
