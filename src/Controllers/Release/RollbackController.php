<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\Target;
use Hal\Core\Repository\ReleaseRepository;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RollbackController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var ReleaseRepository
     */
    private $releaseRepository;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param callable $notFound
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, callable $notFound)
    {
        $this->template = $template;
        $this->releaseRepository = $em->getRepository(Release::class);

        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);

        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return ($this->notFound)($request, $response);
        }

        $releases = $this->releaseRepository->getByTarget($target, SharedStaticConfiguration::SMALL_PAGE_SIZE, ($page - 1));

        $total = count($releases);
        $last = ceil($total / SharedStaticConfiguration::SMALL_PAGE_SIZE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'application' => $application,
            'target' => $target,
            'releases' => $releases
        ]);
    }
}
