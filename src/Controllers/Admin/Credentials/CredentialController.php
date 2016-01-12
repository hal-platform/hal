<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Credentials;

use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Crypto\Decrypter;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class CredentialController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Credential
     */
    private $credential;

    /**
     * @type Decrypter
     */
    private $decrypter;

    /**
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @param TemplateInterface $template
     * @param Credential $credential
     * @param EntityManagerInterface $em
     * @param Decrypter $decrypter
     */
    public function __construct(
        TemplateInterface $template,
        Credential $credential,
        EntityManagerInterface $em,
        $decrypter
    ) {
        $this->template = $template;
        $this->credential = $credential;
        $this->decrypter = $decrypter;

        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $decrypted = false;
        if ($this->credential->type() === 'aws') {
            $decrypted = $this->decrypt($this->credential->aws()->secret());
        }

        $deployments = $this->deploymentRepo->findBy(['credential' => $this->credential]);

        $this->template->render([
            'credential' => $this->credential,
            'deployments' => $deployments,
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
