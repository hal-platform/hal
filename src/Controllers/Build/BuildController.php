<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\ScheduledAction;
use Hal\Core\Entity\Job\JobMeta;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\JobEventsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BuildController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var JobEventsService
     */
    private $eventsService;

    /**
     * @var EntityRepository
     */
    private $scheduledRepo;
    private $releaseRepo;
    private $metaRepo;

    /**
     * @param TemplateInterface $template
     * @param JobEventsService $eventsService
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, JobEventsService $eventsService, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->eventsService = $eventsService;

        $this->scheduledRepo = $em->getRepository(ScheduledAction::class);
        $this->releaseRepo = $em->getRepository(Release::class);
        $this->metaRepo = $em->getRepository(JobMeta::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);

        $scheduledActions = $this->scheduledRepo->findBy(['triggerJob' => $build]);

        $releases = [];
        if ($build->isFinished()) {
            $releases = $this->releaseRepo->findBy(['build' => $build], ['created' => 'DESC']);
        }

        // Resolves logs from redis (for in progress jobs) or db (after completed)
        $events = $this->eventsService->getEvents($build);
        $metadata = $this->metaRepo->findBy(['job' => $build], ['name' => 'ASC']);

        return $this->withTemplate($request, $response, $this->template, [
            'build' => $build,

            'events' => $events,
            'meta' => $metadata,

            'scheduled' => $scheduledActions,
            'releases' => $releases
        ]);
    }

    /**
     * @param JobProcess $process
     *
     * @return array
     */
    // private function formatChild(JobProcess $process)
    // {
    //     $meta = [
    //         'id' => $process->id(),
    //         'status' => $process->status(),
    //         'message' => $process->message()
    //     ];

    //     // For now, just format for autopushes, since thats the only type of process available in v1 of this feature
    //     if ($process->childType() === 'Release') {
    //         $meta += $this->getProcessResources($process);
    //     }

    //     return $meta;
    // }

    /**
     * @param JobProcess $process
     *
     * @return array
     */
    // private function getProcessResources(JobProcess $process)
    // {
    //     $release = $process->childID() ? $this->releaseRepo->find($process->childID()) : null;

    //     $context = $process->parameters();

    //     $target = null;

    //     // Ugh, terrible stuff just to find target deployment
    //     // If a push is found, grab the deployment from the push
    //     if ($release) {
    //         $target = $release->deployment();

    //     // Otherwise, try a lookup of the deployment from context
    //     // This is used if the child hasn't launched yet (or was aborted).
    //     } elseif (isset($context['target'])) {
    //         $target = $this->targetRepo->find($context['target']);
    //     }

    //     return [
    //         'release' => $release,
    //         'target' => $target
    //     ];
    // }
}
