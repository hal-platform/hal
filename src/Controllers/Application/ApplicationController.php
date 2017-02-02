<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em,
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->template->render([
            'application' => $this->application,
            'has_deployments' => $this->doesApplicationHaveChildren()
        ]);
    }

    /**
     * @return bool
     */
    private function doesApplicationHaveChildren()
    {
        $targets = $this->em
            ->getRepository(Deployment::class)
            ->findOneBy(['application' => $this->application]);

        if (count($targets) > 0) return true;

        $builds = $this->em
            ->getRepository(Build::class)
            ->findOneBy(['application' => $this->application]);

        if (count($builds) > 0) return true;

        $deployments = $this->em
            ->getRepository(Push::class)
            ->findOneBy(['application' => $this->application]);

        if (count($deployments) > 0) return true;

        return false;
    }
}
