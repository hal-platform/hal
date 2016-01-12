<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Build;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class BuildController implements ControllerInterface
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $buildRepo;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, EntityManagerInterface $em, array $parameters)
    {
        $this->formatter = $formatter;
        $this->buildRepo = $em->getRepository(Build::CLASS);

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $build = $this->buildRepo->find($this->parameters['id']);

        if (!$build instanceof Build) {
            throw new HTTPProblemException(404, 'Invalid build ID specified');
        }

        $this->formatter->respond($build);
    }
}
