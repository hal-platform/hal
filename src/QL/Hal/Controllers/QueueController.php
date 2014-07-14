<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\Common\Collections\Criteria;
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
     * @param Twig_Template $template
     * @param Layout $layout
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        BuildRepository $buildRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->layout->render($this->template, [
            'jobs' => $this->getPendingJobs()
        ]);

        $response->body($rendered);
    }

    /**
     * @return array
     */
    private function getPendingJobs()
    {
        $buildCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Building'))
            ->orderBy(['created' => 'DESC']);

        $pushCriteria = (new Criteria)
            ->where(Criteria::expr()->eq('status', 'Waiting'))
            ->orWhere(Criteria::expr()->eq('status', 'Pushing'))
            ->orderBy(['created' => 'DESC']);

        $builds = $this->buildRepo->matching($buildCriteria);
        $pushes = $this->pushRepo->matching($pushCriteria);

        return array_merge($builds->toArray(), $pushes->toArray());
    }
}
