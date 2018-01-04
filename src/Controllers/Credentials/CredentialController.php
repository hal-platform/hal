<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Hal\Core\Crypto\Encryption;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\Target;
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
     * @var EntityRepository
     */
    private $targetRepository;

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

        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $credential = $request->getAttribute(Credential::class);

        $targets = $this->targetRepository->findBy(['credential' => $credential]);

        $decrypted = $this->decrypt($credential);


        return $this->withTemplate($request, $response, $this->template, [
            'credential' => $credential,

            'targets' => $targets,
            'decrypted' => $decrypted,
            'is_decryption_error' => ($decrypted === null)
        ]);
    }

    /**
     * @param Credential $credential
     *
     * @return string|bool|null
     */
    private function decrypt(Credential $credential)
    {
        $decrypted = false;
        $secret = '';

        if ($credential->type() === CredentialEnum::TYPE_AWS_STATIC) {
            $secret = $credential->details()->secret();
        }

        if ($credential->type() === CredentialEnum::TYPE_PRIVATEKEY) {
            $secret = $credential->details()->file();
        }

        if ($secret) {
            $decrypted = $this->encryption->decrypt($secret);
        }

        return $decrypted;
    }
}
