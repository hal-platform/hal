<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Hal\Core\Crypto\Encryption;
use Hal\Core\Entity\Credential;
use Hal\Core\Type\CredentialEnum;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class CredentialController implements ControllerInterface
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
     * @param EntityManagerInterface $em
     * @param Encryption $encryption
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Encryption $encryption
    ) {
        $this->template = $template;
        $this->encryption = $encryption;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $credential = $request->getAttribute(Credential::class);

        $decrypted = false;
        if ($credential->type() === CredentialEnum::TYPE_AWS_STATIC) {
            $decrypted = $this->decrypt($credential->details()->secret());

        } elseif ($credential->type() === CredentialEnum::TYPE_PRIVATEKEY) {
            if ($credential->details()->file()) {
                $decrypted = $this->decrypt($credential->details()->file());
            }
        }

        return $this->withTemplate($request, $response, $this->template, [
            'credential' => $credential,

            'decrypted' => $decrypted,
            'is_decryption_error' => ($decrypted === null)
        ]);
    }

    /**
     * @param string $encrypted
     *
     * @return string|bool|null
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
