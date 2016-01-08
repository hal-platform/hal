<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Type\EnumType\PushStatusEnum;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;
use Slim\Http\Request;

class LastPushController implements ControllerInterface
{
    const FILTER_STATUS = 'status';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param Request $request
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param array $parameters
     */
    public function __construct(
        Request $request,
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        array $parameters
    ) {
        $this->request = $request;
        $this->formatter = $formatter;

        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $deployment = $this->deploymentRepo->find($this->parameters['id']);

        if (!$deployment instanceof Deployment) {
            throw new HTTPProblemException(404, 'Invalid deployment ID specified');
        }

        $status = $this->request->get(self::FILTER_STATUS);

        if ($status && !in_array($status, PushStatusEnum::values())) {
            throw new HTTPProblemException(400, 'Invalid push status specified');
        }

        if ($status === 'Success') {
            $push = $this->pushRepo->getMostRecentSuccessByDeployment($deployment);
        } else {
            $push = $this->pushRepo->getMostRecentByDeployment($deployment);
        }

        if (!$push) {
            throw new HTTPProblemException(404, 'No push found for this deployment found');
        }

        $this->formatter->respond($push);
    }
}
