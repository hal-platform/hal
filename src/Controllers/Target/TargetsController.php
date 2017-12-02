<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Type\EnumType\GroupEnum;
use Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class TargetsController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $groupRepo;
    private $applicationRepo;
    private $targetRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->environmentRepo = $em->getRepository(Environment::class);
        $this->groupRepo = $em->getRepository(Group::class);
        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $environments = $this->getEnvironmentsAsAssocArray();

        return $this->withTemplate($request, $response, $this->template, [
            'environments' => $environments,
            'application' => $application,

            'targets_by_env' => $this->environmentalizeTargets($application, $environments)
        ]);
    }

    /**
     * @return Environment[]
     */
    private function getEnvironmentsAsAssocArray()
    {
        $environments = $this->environmentRepo->getAllEnvironmentsSorted();

        $envs = [];
        foreach ($environments as $env) {
            $envs[$env->name()] = $env;
        }

        return $envs;
    }

    /**
     * @param Application $application
     * @param Environment[] $environments
     *
     * @return array
     */
    private function environmentalizeTargets(Application $application, array $environments)
    {
        $targets = $this->targetRepo->findBy(['application' => $application]);
        $sorter = $this->targetSorter();
        usort($targets, $sorter);

        $env = [];
        foreach ($environments as $environment) {
            $env[$environment->name()] = [];
        }

        foreach ($targets as $target) {
            $name = $target->group()->environment()->name();
            $env[$name][] = $target;
        }

        return $env;
    }
}
