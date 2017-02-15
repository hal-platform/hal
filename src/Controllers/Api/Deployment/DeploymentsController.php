<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Normalizer\DeploymentNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;
use Slim\Http\Request;

class DeploymentsController implements ControllerInterface
{
    use HypermediaResourceTrait;
    use SortingTrait;

    const FILTER_ENVIRONMENT = 'environment';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $environmentRepo;

    /**
     * @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var DeploymentNormalizer
     */
    private $normalizer;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param Request $request
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param DeploymentNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        Request $request,
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        DeploymentNormalizer $normalizer,
        array $parameters
    ) {
        $this->request = $request;
        $this->formatter = $formatter;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->environmentRepo = $em->getRepository(Environment::CLASS);

        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $application = $this->getApplication();
        $environment = $this->getEnvironment();

        if ($environment) {
            $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($application, $environment);
        } else {
            $deployments = $this->deploymentRepo->findBy(['application' => $application]);
        }

        usort($deployments, $this->deploymentSorter());

        $deployments = array_map(function ($deployment) {
            return $this->normalizer->link($deployment);
        }, $deployments);

        $resource = $this->buildResource(
            [
                'count' => count($deployments)
            ],
            [],
            [
                'deployments' => $deployments
            ]
        );

        $status = (count($deployments) > 0) ? 200 : 404;
        $this->formatter->respond($resource, $status);
    }

    /**
     * @throws HTTPProblemException
     *
     * @return Application
     */
    private function getApplication()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);

        if (!$application instanceof Application) {
            throw new HTTPProblemException(404, 'Invalid application ID specified');
        }

        return $application;
    }

    /**
     * @throws HTTPProblemException
     *
     * @return Environment|null
     */
    private function getEnvironment()
    {
        $env = $this->request->get(self::FILTER_ENVIRONMENT);

        if ($env === null) {
            return null;
        }

        // try by id
        if ($environment = $this->environmentRepo->find($env)) {
            return $environment;
        }

        // try by name
        if ($environment = $this->environmentRepo->findOneBy(['name' => strtolower($env)])) {
            return $environment;
        }

        throw new HTTPProblemException(404, 'Invalid environment ID or name specified');
    }
}
