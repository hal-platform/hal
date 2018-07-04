<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Queue;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Repository\JobType\BuildRepository;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class BuildsHistoryController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->buildRepo = $em->getRepository(Build::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $page = $this->getCurrentPage($request);

        $builds = $this->buildRepo->getPagedResults(SharedStaticConfiguration::LARGE_PAGE_SIZE, ($page - 1));
        $last = $this->getLastPage($builds, SharedStaticConfiguration::LARGE_PAGE_SIZE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'builds' => $builds,
        ]);
    }
}
