<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;

class StatusController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $tarRepository;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     *
     * @param $em
     *
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        $em,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->tarRepository = $this->em->getRepository(Target::CLASS);

        $this->notFound = $notFound;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $targets = $this->tarRepository->findBy(['application' => $this->application]);

        if (!$targets) {
            return call_user_func($this->notFound);
        }

        // Cross reference checksum of current value in Consul with checksum of "active" configuration in DB

        $context = [
            'application' => $this->application,
            'targets' => $targets
        ];

        $this->template->render($context);
    }
}
