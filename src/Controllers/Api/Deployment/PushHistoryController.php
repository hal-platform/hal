<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Type\EnumType\PushStatusEnum;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class PushHistoryController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

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
     * @type PushNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param PushNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        PushNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $deployment = $this->getDeployment();
        $page = $this->getCurrentPage();

        $pagination = $this->pushRepo->getByDeployment($deployment, self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $pushes = [];
        foreach ($pagination as $push) {
            $pushes[] = $this->normalizer->link($push);
        }

        $links = $this->buildPaginationLinks($page, $total, $deployment);
        $links['pushes'] = $pushes;

        $resource = $this->buildResource(
            [
                'count' => count($pushes),
                'total' => $total,
                'page' => $page
            ],
            [],
            $links
        );

        $status = (count($pushes) > 0) ? 200 : 404;
        $this->formatter->respond($resource, $status);
    }

    /**
     * @throws HTTPProblemException
     *
     * @return Deployment
     */
    private function getDeployment()
    {
        $deployment = $this->deploymentRepo->find($this->parameters['id']);

        if (!$deployment instanceof Deployment) {
            throw new HTTPProblemException(404, 'Invalid deployment ID specified');
        }

        return $deployment;
    }

    /**
     * @throws HTTPProblemException
     *
     * @return int
     */
    private function getCurrentPage()
    {
        $page = (isset($this->parameters['page'])) ? intval($this->parameters['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            throw new HTTPProblemException(404, 'Invalid page specified');
        }

        return $page;
    }

    /**
     * @param int $current
     * @param int $last
     * @param Deployment $deployment
     *
     * @return array
     */
    private function buildPaginationLinks($current, $total, Deployment $deployment)
    {
        $links = [];

        $prev = $current - 1;
        $next = $current + 1;
        $last = ceil($total / self::MAX_PER_PAGE);

        if ($current > 1) {
            $links['prev'] = ['href' => ['api.deployment.history.paged', ['id' => $deployment->id(), 'page' => $prev]]];
        }

        if ($next <= $last) {
            $links['next'] = ['href' => ['api.deployment.history.paged', ['id' => $deployment->id(), 'page' => $next]]];
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = ['href' => ['api.deployment.history.paged', ['id' => $deployment->id(), 'page' => '1']]];
        }

        if ($last > 1) {
            $links['last'] = ['href' => ['api.deployment.history.paged', ['id' => $deployment->id(), 'page' => $last]]];
        }

        return $links;
    }
}
