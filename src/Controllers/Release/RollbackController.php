<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RollbackController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    private const MAX_PER_PAGE = 25;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PushRepository
     */
    private $pushRepo;

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
        $this->pushRepo = $em->getRepository(Push::class);

        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $deployment = $request->getAttribute(Deployment::class);

        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return ($this->notFound)($request, $response);
        }

        $pushes = $this->pushRepo->getByDeployment($deployment, self::MAX_PER_PAGE, ($page-1));

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'application' => $application,
            'deployment' => $deployment,
            'pushes' => $pushes
        ]);
    }
}
