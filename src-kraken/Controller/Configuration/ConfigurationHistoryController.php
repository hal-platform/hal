<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Core\Repository\ConfigurationRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ConfigurationHistoryController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $targetRepo;

    /**
     * @type ConfigurationRepository
     */
    private $configurationRepo;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Environment $environment
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        EntityManagerInterface $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->configurationRepo = $em->getRepository(Configuration::CLASS);

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $configurations = $this->configurationRepo->getByApplication($this->application, self::MAX_PER_PAGE, ($page-1));
        $deployed = $this->getDeployedConfigurations($this->application);

        $total = count($configurations);
        $last = ceil($total / self::MAX_PER_PAGE);

        $this->template->render([
            'page' => $page,
            'last' => $last,

            'application' => $this->application,
            'configurations' => $configurations,
            'deployed' => $deployed
        ]);
    }

    /**
     * Get deployed configurations for an application.
     *
     * @param Application $application
     *
     * @return array
     */
    public function getDeployedConfigurations(Application $application)
    {
        $deployed = [];

        $targets = $this->targetRepo->findBy(['application' => $application]);
        foreach ($targets as $target) {
            if (!$target->configuration()) continue;

            $id = $target->configuration()->id();
            $deployed[$id] = $id;
        }

        return $deployed;
    }
}
