<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class EnvironmentController implements ControllerInterface
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $envRepo;

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
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $environment = $this->envRepo->find($this->parameters['id']);

        if (!$environment instanceof Environment) {
            throw new HTTPProblemException(404, 'Invalid environment ID specified');
        }

        $this->formatter->respond($environment);
    }
}
