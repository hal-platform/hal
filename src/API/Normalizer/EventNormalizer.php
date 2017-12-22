<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\JobEvent;
use Hal\Core\Entity\Release;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;

class EventNormalizer implements ResourceNormalizerInterface
{
    /**
     * @var EntityRepository
     */
    private $buildRepository;
    private $releaseRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->buildRepository = $entityManager->getRepository(Build::class);
        $this->releaseRepository = $entityManager->getRepository(Release::class);
    }

    /**
     * @param JobEvent $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param JobEvent|null $event
     *
     * @return Hyperlink|null
     */
    public function link($event): ?Hyperlink
    {
        if (!$event instanceof JobEvent) {
            return null;
        }

        $title = sprintf('[%s] %s', $event->order(), $event->message());

        return new Hyperlink(
            ['api.event', ['event' => $event->id()]],
            $title
        );
    }

    /**
     * @param JobEvent|null $event
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($event, array $embed = []): ?HypermediaResource
    {
        if (!$event instanceof JobEvent) {
            return null;
        }

        $data = [
            'id' => $event->id(),
            'name' => $event->stage(),
            'order' => $event->order(),
            'message' => $event->message(),
            'status' => $event->status(),
            'created' => $event->created(),
            'data' => '**DATA**'
        ];

        if (in_array('data', $embed)) {
            $data['data'] = $event->parameters();
        }

        $links = [
            'self' => $this->link($event)
        ];

        $build = null;
        if (mb_substr($event->parentID(), 0, 1)  == 'b') {
            $build = $this->buildRepository->find($event->parentID());
        }

        $release = null;
        if (mb_substr($event->parentID(), 0, 1)  == 'r') {
            $release = $this->releaseRepository->find($event->parentID());
        }

        $resource = new HypermediaResource($data, $links, [
            'build' => $build,
            'release' => $release
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
