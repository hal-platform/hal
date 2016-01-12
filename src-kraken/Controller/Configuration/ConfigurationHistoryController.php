<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Core\Repository\ConfigurationRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class ConfigurationHistoryController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    const REGEX_ENV = '/(?:environment|env|e):([a-zA-Z-]+)/';

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
    private $environmentRepo;

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
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        EntityManagerInterface $em,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->environmentRepo = $em->getRepository(Environment::CLASS);
        $this->configurationRepo = $em->getRepository(Configuration::CLASS);

        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;
        $searchFilter = is_string($this->request->get('search')) ? $this->request->get('search') : '';

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $environment = $this->getEnvironmentFromSearchFilter($searchFilter);

        if ($environment) {
            $configurations = $this->configurationRepo->getByApplicationForEnvironment(
                $this->application,
                $environment,
                self::MAX_PER_PAGE,
                ($page-1)
            );

        } elseif ($environment === false) {
            // No env provided in search query
            $configurations = $this->configurationRepo->getByApplication(
                $this->application,
                self::MAX_PER_PAGE,
                ($page-1)
            );

        } else {
            $configurations = [];
        }

        $deployed = ($configurations) ? $this->getDeployedConfigurations($this->application) : [];
        $total = count($configurations);
        $last = ceil($total / self::MAX_PER_PAGE);

        $this->template->render([
            'page' => $page,
            'last' => $last,

            'application' => $this->application,
            'configurations' => $configurations,
            'deployed' => $deployed,

            'search_filter' => $searchFilter
        ]);
    }

    /**
     * Get deployed configurations for an application.
     *
     * @param Application $application
     *
     * @return array
     */
    private function getDeployedConfigurations(Application $application)
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

    /**
     * @param string $search
     *
     * @return Environment|null|false
     */
    private function getEnvironmentFromSearchFilter($search)
    {
        if (preg_match(self::REGEX_ENV, $search, $matches) === 1) {
            $name = strtolower(array_pop($matches));
            return $this->environmentRepo->findOneBy(['name' => $name]);
        }

        return false;
    }
}
