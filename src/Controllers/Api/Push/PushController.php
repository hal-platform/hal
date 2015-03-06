<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class PushController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param PushRepository $pushRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, PushRepository $pushRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->pushRepo = $pushRepo;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $push = $this->pushRepo->find($this->parameters['id']);

        if (!$push instanceof Push) {
            throw HttpProblemException::build(404, 'invalid-push');
        }

        $this->formatter->respond($push);
    }
}
