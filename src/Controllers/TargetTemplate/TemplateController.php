<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetTemplate;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class TemplateController implements ControllerInterface
{
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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $template = $request->getAttribute(TargetTemplate::class);

        $targets = $this->targetRepo->findBy(['template' => $template]);

        usort($targets, $this->sorterTargetsByApplication());

        return $this->withTemplate($request, $response, $this->template, [
            'template' => $template,
            'targets' => $targets
        ]);
    }

    /**
     * @return callable
     */
    private function sorterTargetsByApplication()
    {
        return function ($a, $b) {
            $nameA = $a->application()->name();
            $nameB = $b->application()->name();

            if ($a->application() === $b->application()) {
                $nameA = $a->name();
                $nameB = $b->name();
            }

            return strcasecmp($nameA, $nameB);
        };
    }
}
