<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\VCS;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class VersionControlController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->applicationRepo = $em->getRepository(Application::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $vcs = $request->getAttribute(VersionControlProvider::class);

        return $this->withTemplate($request, $response, $this->template, [
            'vcs' => $vcs,
            'can_remove' => $this->canVCSBeRemoved($vcs)
        ]);
    }

    /**
     * @param VersionControlProvider $vcs
     *
     * @return bool
     */
    private function canVCSBeRemoved(VersionControlProvider $vcs)
    {
        $hasApplications = $this->applicationRepo->findOneBy(['provider' => $vcs]);

        return ($hasApplications === null);
    }
}
