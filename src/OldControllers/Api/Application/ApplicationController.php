<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class ApplicationController implements ControllerInterface
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

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
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);

        if (!$application instanceof Application) {
            throw new HTTPProblemException(404, 'Invalid application ID specified');
        }

        $this->formatter->respond($application);
    }
}
