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
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Process;
use QL\Hal\Core\Entity\Push;
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
    private $processRepo;
    private $pushRepo;
    private $deploymentRepo;

    /**
     * @param TemplateInterface $template
     * @param EventLogService $logService
     * @param EntityManager $em
     */
    public function __construct(TemplateInterface $template, EventLogService $logService, EntityManager $em)
    {
        $this->template = $template;
        $this->logService = $logService;

        $this->processRepo = $em->getRepository(Process::class);
        $this->pushRepo = $em->getRepository(Push::class);
        $this->deploymentRepo = $em->getRepository(Deployment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);

        $processes = $this->processRepo->findBy([
            'parent' => $build->id()
        ]);

        // Queries in loops SUUUUUUCK
        $children = array_map(function($child) {
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
     * @param Process $child
     *
     * @return array
     */
    private function formatChild(Process $process)
    {
        $meta = [
            'id' => $process->id(),
            'status' => $process->status(),
            'message' => $process->message()
        ];

        // For now, just format for autopushes, since thats the only type of process available in v1 of this feature
        if ($process->childType() === 'Push') {
            $meta += $this->getProcessResources($process);
        }

        return $meta;
    }

    /**
     * @param Process $process
     *
     * @return array
     */
    private function getProcessResources(Process $process)
    {
        $push = $process->child() ? $this->pushRepo->find($process->child()) : null;

        $context = $process->context();

        $deployment = null;

        // Ugh, terrible stuff just to find target deployment
        // If a push is found, grab the deployment from the push
        if ($push) {
            $deployment = $push->deployment();

        // Otherwise, try a lookup of the deployment from context
        // This is used if the child hasn't launched yet (or was aborted).
        } elseif (isset($context['deployment'])) {
            $deployment = $this->deploymentRepo->find($context['deployment']);
        }

        return [
            'push' => $push,
            'deployment' => $deployment
        ];
    }
}
