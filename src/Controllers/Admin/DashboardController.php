<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DashboardController implements ControllerInterface
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
    public function __construct(TemplateInterface $template, $encryptionKey, $sessionEncryptionKey, $halPushFile)
    {
        $this->template = $template;

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
            'session_encryption_key' => $this->sessionEncryptionKey
        ];

        # add hal push file if possible.
        if (file_exists($this->halPushFile)) {
            $context['pushfile'] = file_get_contents($this->halPushFile);
        }

        $this->template->render($context);
    }
}
