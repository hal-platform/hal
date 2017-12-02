<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\UserSettings;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\JobQueueService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DashboardController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var JobQueueService
     */
    private $queue;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param JobQueueService $queue
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        JobQueueService $queue
    ) {
        $this->template = $template;
        $this->queue = $queue;

        $this->applicationRepo = $em->getRepository(Application::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);

        return $this->withTemplate($request, $response, $this->template, [
            'favorites' => $this->findFavorites($user),
            'pending' => $this->queue->getPendingJobs()
        ]);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    private function findFavorites(User $user): array
    {
        if (!$favorites = $user->settings()->favoriteApplications()) {
            return [];
        }

        $criteria = (new Criteria)
            ->where(Criteria::expr()->in('id', $favorites));

        $apps = $this->applicationRepo->matching($criteria);

        return $apps->toArray();
    }
}
