<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Job\JobMeta;
use Hal\Core\Entity\JobType\Release;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\JobEventsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReleaseController implements ControllerInterface
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

        $this->metaRepo = $em->getRepository(JobMeta::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $release = $request->getAttribute(Release::class);

        // Resolves logs from redis (for in progress jobs) or db (after completed)
        $events = $this->eventsService->getEvents($release);
        $metadata = $this->metaRepo->findBy(['job' => $release], ['name' => 'ASC']);

        return $this->withTemplate($request, $response, $this->template, [
            'release' => $release,

            'events' => $events,
            'meta' => $metadata,
        ]);
    }
}
