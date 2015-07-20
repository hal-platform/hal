<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @param TemplateInterface $template
     * @param Credential $credential
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, Credential $credential, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->credential = $credential;

        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $deployments = $this->deploymentRepo->findBy(['credential' => $this->credential]);

        $this->template->render([
            'credential' => $this->credential,
            'deployments' => $deployments,
        ]);
    }
}
