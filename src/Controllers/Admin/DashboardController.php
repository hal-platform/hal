<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DashboardController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var string
     */
    private $encryptionKey;

    /**
     * @var string
     */
    private $sessionEncryptionKey;

    /**
     * @var string
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
     * @inheritDoc
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
