<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\EventLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\JobProcess;
use Hal\Core\Entity\Release;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class BuildController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EventLogService
     */
    private $logService;

    /**
     * @var EntityRepository
     */
    private $processRepository;
    private $releaseRepository;
    private $targetRepository;

    /**
     * @param TemplateInterface $template
     * @param EventLogService $logService
     * @param EntityManager $em
     */
    public function __construct(TemplateInterface $template, EventLogService $logService, EntityManager $em)
    {
        $this->template = $template;
        $this->logService = $logService;

        $this->processRepository = $em->getRepository(JobProcess::class);
        $this->releaseRepository = $em->getRepository(Release::class);
        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);

        $processes = $this->processRepository->findBy([
            'parentID' => $build->id()
        ]);

        // Queries in loops SUUUUUUCK
        $children = array_map(function ($child) {
            return $this->formatChild($child);
        }, $processes);

        // Resolves logs from redis (for in progress jobs) or db (after completed)
        $logs = $this->logService->getLogs($build);

        return $this->withTemplate($request, $response, $this->template, [
            'build' => $build,
            'children' => $children,
            'logs' => $logs
        ]);
    }

    /**
     * @param JobProcess $child
     *
     * @return array
     */
    private function formatChild(JobProcess $process)
    {
        $meta = [
            'id' => $process->id(),
            'status' => $process->status(),
            'message' => $process->message()
        ];

        // For now, just format for autopushes, since thats the only type of process available in v1 of this feature
        if ($process->childType() === 'Release') {
            $meta += $this->getProcessResources($process);
        }

        return $meta;
    }

    /**
     * @param JobProcess $process
     *
     * @return array
     */
    private function getProcessResources(JobProcess $process)
    {
        $release = $process->childID() ? $this->releaseRepository->find($process->childID()) : null;

        $context = $process->parameters();

        $target = null;

        // Ugh, terrible stuff just to find target deployment
        // If a push is found, grab the deployment from the push
        if ($release) {
            $target = $release->deployment();

        // Otherwise, try a lookup of the deployment from context
        // This is used if the child hasn't launched yet (or was aborted).
        } elseif (isset($context['target'])) {
            $target = $this->targetRepository->find($context['target']);
        }

        return [
            'release' => $release,
            'target' => $target
        ];
    }
}
