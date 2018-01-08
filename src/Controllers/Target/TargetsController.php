<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
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
use Hal\Core\Repository\EnvironmentRepository;
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
        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $environments = $this->environmentRepo->getAllEnvironmentsSorted();
        $targets = $this->targetRepo->findBy(['application' => $application]);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,

            'sorted_targets' => $this->environmentalizeTargets($environments, $targets)
        ]);
    }

    /**
     * @param array $environments
     * @param array $targets
     *
     * @return array
     */
    private function environmentalizeTargets(array $environments, array $targets)
    {
        $sorter = $this->targetSorter();
        usort($targets, $sorter);

        $sorted = [];

        foreach ($environments as $environment) {
            $sorted[$environment->id()] = [
                'environment' => $environment,
                'targets' => []
            ];
        }

        foreach ($targets as $target) {
            $id = $target->environment()->id();
            $sorted[$id]['targets'][] = $target;
        }

        return array_filter($sorted, function($e) {
            return count($e['targets']) !== 0;
        });
    }
}
