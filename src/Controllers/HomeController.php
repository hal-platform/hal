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
use Hal\Core\Entity\Job;
use Hal\Core\Entity\User;
use Hal\Core\Type\JobStatusEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class HomeController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $jobRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::class);
        $this->jobRepo = $em->getRepository(Job::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);

        return $this->withTemplate($request, $response, $this->template, [
            'favorites' => $this->findFavorites($user),
            'pending' => $this->findPendingJobs(),
        ]);
    }

    /**
     * @return array
     */
    private function findPendingJobs()
    {
        return $this->jobRepo->findBy(
            [
                'status' => [JobStatusEnum::TYPE_PENDING, JobStatusEnum::TYPE_RUNNING],
            ],
            [
                'created' => 'DESC',
            ],
        );
    }

    /**
     * @param User $user
     *
     * @return array
     */
    private function findFavorites(User $user): array
    {
        if (!$favorites = $user->setting('favorite_applications')) {
            return [];
        }

        $criteria = (new Criteria)
            ->where(Criteria::expr()->in('id', $favorites));

        $apps = $this->applicationRepo->matching($criteria);

        return $apps->toArray();
    }
}
