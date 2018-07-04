<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\VCS;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Repository\System\VersionControlProviderRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class VersionControlProvidersController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var VersionControlProviderRepository
     */
    private $vcsRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->vcsRepo = $em->getRepository(VersionControlProvider::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->withTemplate($request, $response, $this->template, [
            'vcs_providers' => $this->vcsRepo->findAll(),
            'application_counts' => $this->vcsRepo->getApplicationCounts(),
        ]);
    }
}
