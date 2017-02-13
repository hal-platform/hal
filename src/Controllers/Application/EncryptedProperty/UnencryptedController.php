<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\EncryptedProperty;

use Exception;
use QL\Hal\Core\Crypto\Decrypter;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class UnencryptedController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EncryptedProperty
     */
    private $encrypted;

    /**
     * @var Decrypter
     */
    private $decrypter;

    /**
     * @param TemplateInterface $template
     * @param EncryptedProperty $encrypted
     * @param Decrypter $decrypter
     */
    public function __construct(
        TemplateInterface $template,
        EncryptedProperty $encrypted,
        $decrypter
    ) {
        $this->template = $template;
        $this->encrypted = $encrypted;

        // Lazy load!
        $this->decrypter = $decrypter;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $decrypted = $this->decrypt($this->encrypted->data());

        $this->template->render([
            'application' => $this->encrypted->application(),
            'encrypted' => $this->encrypted,
            'decrypted' => $decrypted,
            'decryption_error' => ($decrypted === null)
        ]);
    }

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    private function decrypt($encrypted)
    {
        try {
            $decrypted = $this->decrypter->decrypt($encrypted);
            return $decrypted;

        } catch (Exception $ex) {
            return null;
        }
    }
}
