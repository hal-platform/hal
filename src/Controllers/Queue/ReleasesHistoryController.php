<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Repository\JobType\ReleaseRepository;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ReleasesHistoryController implements ControllerInterface
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
    private $releaseRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->releaseRepo = $em->getRepository(Release::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $page = $this->getCurrentPage($request);

        $releases = $this->releaseRepo->getPagedResults(SharedStaticConfiguration::LARGE_PAGE_SIZE, ($page - 1));
        $last = $this->getLastPage($releases, SharedStaticConfiguration::LARGE_PAGE_SIZE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'releases' => $releases
        ]);
    }
}
