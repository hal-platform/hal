<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Crypto\Decrypter;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
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
     * @var Decrypter
     */
    private $decrypter;

    /**
     * @var EntityRepository
     */
    private $targetRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Decrypter $decrypter
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        $decrypter
    ) {
        $this->template = $template;
        $this->decrypter = $decrypter;

        $this->targetRepo = $em->getRepository(Deployment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $credential = $request->getAttribute(Credential::class);

        $decrypted = false;
        if ($credential->type() === 'aws') {
            $decrypted = $this->decrypt($credential->aws()->secret());
        }

        $targets = $this->targetRepo->findBy(['credential' => $credential]);

        return $this->withTemplate($request, $response, $this->template, [
            'credential' => $credential,
            'targets' => $targets,
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
