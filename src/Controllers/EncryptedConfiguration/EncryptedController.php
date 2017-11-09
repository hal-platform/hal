<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\EncryptedConfiguration;

use Exception;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Crypto\Encryption;
use Hal\Core\Entity\EncryptedProperty;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EncryptedController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @param TemplateInterface $template
     * @param Encryption $encryption
     */
    public function __construct(TemplateInterface $template, Encryption $encryption)
    {
        $this->template = $template;
        $this->encryption = $encryption;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $encrypted = $request->getAttribute(EncryptedProperty::class);
        $decrypted = $this->decrypt($encrypted->data());

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $encrypted->application(),
            'encrypted' => $encrypted,

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
            $decrypted = $this->encryption->decrypt($encrypted);
            return $decrypted;

        } catch (Exception $ex) {
            return null;
        }
    }
}
