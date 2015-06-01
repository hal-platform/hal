<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class BuildsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $buildRepo;

    /**
     * @type BuildNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param BuildNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        BuildNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);

        if (!$application instanceof Application) {
            throw HttpProblemException::build(404, 'invalid-application');
        }

        $builds = $this->buildRepo->findBy(['application' => $application], ['status' => 'ASC', 'start' => 'DESC']);
        $status = (count($builds) > 0) ? 200 : 404;

        $builds = array_map(function ($build) {
            return $this->normalizer->link($build);
        }, $builds);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($builds)
            ],
            [],
            [
                'builds' => $builds
            ]
        ), $status);
    }

    /**
     * @param array $builds
     * @param boolean $isResolved
     * @return array
     */
    private function normalizeBuilds(array $builds, $isResolved)
    {
        $normalized = array_map(function($build) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($build);
            }

            return $this->normalizer->linked($build);
        }, $builds);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'builds' => $normalized
            ]
        ];
    }
}
