<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\TargetRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class GroupController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;
    /**
     * @var TargetRepository
     */
    private $targetRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * The primary action of this controller.
     *
     * Must return ResponseInterface.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $group = $request->getAttribute(Group::class);
        $targets = $this->targetRepo->findBy(['group' => $group]);

        usort($targets, function ($a, $b) {
            $appA = $a->application()->name();
            $appB = $b->application()->name();

            return strcasecmp($appA, $appB);
        });

        return $this->withTemplate($request, $response, $this->template, [
            'group' => $group,
            'targets' => $targets
        ]);
    }
}
