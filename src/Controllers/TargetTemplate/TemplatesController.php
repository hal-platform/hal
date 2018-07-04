<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetTemplate;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Repository\TargetTemplateRepository;
use Hal\Core\Utility\SortingTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class TemplatesController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var TargetTemplateRepository
     */
    private $templateRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->templateRepo = $em->getRepository(TargetTemplate::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $templates = $this->templateRepo->getGroupedTemplates();

        return $this->withTemplate($request, $response, $this->template, [
            'sorted_templates' => $templates,
        ]);
    }
}
