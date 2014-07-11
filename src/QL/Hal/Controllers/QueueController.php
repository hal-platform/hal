<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class QueueController
{
    /**
     * @var Twig_Template
     */
    private $template;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param EntityManager $entityManager
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        EntityManager $entityManager
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        $jobs = $this->getPendingJobs();
        usort($jobs, $this->queueSort());

        $rendered = $this->layout->render($this->template, [
            'jobs' => $jobs
        ]);

        $response->body($rendered);
    }

    /**
     * @return array
     */
    private function getPendingJobs()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->select('b')
            ->from('QL\Hal\Core\Entity\Build', 'b')
            ->where('b.status = ?1 OR b.status = ?2')
            ->setParameters([1 => 'Waiting', 2 => 'Building']);
        $builds = $query->getQuery()->getResult();

        $query = $queryBuilder
            ->select('p')
            ->from('QL\Hal\Core\Entity\Push', 'p')
            ->where('p.status = ?1 OR p.status = ?2')
            ->setParameters([1 => 'Waiting', 2 => 'Pushing']);
        $pushes = $query->getQuery()->getResult();

        return array_merge($builds, $pushes);
    }

    /**
     * @return Closure
     */
    private function queueSort()
    {
        return function($aEntity, $bEntity) {
            $a = $aEntity->getCreated();
            $b = $bEntity->getCreated();

            if ($a === $b) {
                return 0;
            }

            if ($a === null xor $b === null) {
                return ($a === null) ? 0 : 1;
            }

            if ($a < $b) {
                return 1;
            }

            return -1;
        };
    }
}
