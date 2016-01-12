<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Process;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Service\EventLogService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class BuildController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Build
     */
    private $build;

    /**
     * @type EventLogService
     */
    private $logService;

    /**
     * @type EntityRepository
     */
    private $processRepo;
    private $pushRepo;
    private $deploymentRepo;

    /**
     * @param TemplateInterface $template
     * @param Build $build
     * @param EventLogService $logService
     * @param EntityManager $em
     */
    public function __construct(
        TemplateInterface $template,
        Build $build,
        EventLogService $logService,
        EntityManager $em
    ) {
        $this->template = $template;

        $this->build = $build;
        $this->logService = $logService;

        $this->processRepo = $em->getRepository(Process::class);
        $this->pushRepo = $em->getRepository(Push::class);
        $this->deploymentRepo = $em->getRepository(Deployment::class);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $processes = $this->processRepo->findBy([
            'parent' => $this->build->id()
        ]);

        $children = array_map(function($child) {
            return $this->formatChild($child);
        }, $processes);

        $this->template->render([
            'build' => $this->build,
            'children' => $children,
            'logs' => $this->logService->getLogs($this->build)
        ]);
    }

    /**
     * @param Process $child
     *
     * @return array
     */
    private function formatChild(Process $process)
    {
        $message = $process->message();

        $context = $process->context();

        $push = $deployment = null;

        // For now, just format for autopushes, since thats the only type of process available in v1 of this feature
        if ($process->childType() === 'Push') {

            // Ugh, terrible stuff just to find target deployment
            $push = $process->child() ? $this->pushRepo->find($process->child()) : null;

            if ($push) {
                $deployment = $push->deployment();
            } elseif (isset($context['deployment'])) {
                $deployment = $this->deploymentRepo->find($context['deployment']);
            }
        }

        return [
            'id' => $process->id(),
            'status' => $process->status(),
            'message' => $message,

            'push' => $push,
            'deployment' => $deployment
        ];
    }
}
