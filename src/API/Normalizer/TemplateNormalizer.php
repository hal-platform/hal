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
use Hal\Core\Entity\TargetTemplate;

class TemplateNormalizer implements ResourceNormalizerInterface
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
     * @param TargetTemplate $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        if (!$input instanceof TargetTemplate) {
            return null;
        }

        return $this->resource($input);
    }

    /**
     * @param TargetTemplate|null $template
     *
     * @return Hyperlink|null
     */
    public function link($template): ?Hyperlink
    {
        if (!$template instanceof TargetTemplate) {
            return null;
        }

        return new Hyperlink(
            ['api.template', ['template' => $template->id()]],
            $template->name()
        );
    }

    /**
     * @param TargetTemplate|null $template
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($template, array $embed = ['environment']): ?HypermediaResource
    {
        if (!$template instanceof TargetTemplate) {
            return null;
        }

        $data = [
            'id' => $template->id(),
            'type' => $template->type(),
            'name' => $template->name(),
        ];

        $links = [
            'self' => $this->link($template),
        ];

        $targets = $this->targetRepository->findBy(['template' => $template->id()]);

        $resource = new HypermediaResource($data, $links, [
            'environment' => $template->environment(),
            'targets' => $targets,
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
