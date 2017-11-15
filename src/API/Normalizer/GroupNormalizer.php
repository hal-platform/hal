<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Utility\SortingTrait;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Group;

class GroupNormalizer implements ResourceNormalizerInterface
{
    use SortingTrait;

    /**
     * @var EntityRepository
     */
    private $targetRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->targetRepository = $entityManager->getRepository(Target::class);
    }

    /**
     * @param Group $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        if (!$input instanceof Group) {
            return null;
        }

        return $this->resource($input);
    }

    /**
     * @param Group|null $group
     *
     * @return Hyperlink|null
     */
    public function link($group): ?Hyperlink
    {
        if (!$group instanceof Group) {
            return null;
        }

        return new Hyperlink(
            ['api.group', ['group' => $group->id()]],
            $group->format()
        );
    }

    /**
     * @param Group|null $group
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($group, array $embed = ['environment']): ?HypermediaResource
    {
        if (!$group instanceof Group) {
            return null;
        }

        $data = [
            'id' => $group->id(),
            'type' => $group->type(),
            'name' => $group->name()
        ];

        $links = [
            'self' => $this->link($group)
        ];

        $targets = $this->targetRepository->findBy(['group' => $group->id()]);

        $resource = new HypermediaResource($data, $links, [
            'environment' => $group->environment(),
            'targets' => $targets
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
