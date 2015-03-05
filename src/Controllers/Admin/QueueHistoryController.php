<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class QueueHistoryController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManager $em
     * @param Response $response
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManager $em,
        Response $response,
        array $parameters
    ) {
        $this->template = $template;
        $this->em = $em;

        $this->response = $response;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $page = (isset($this->parameters['page']) && $this->parameters['page'] > 1) ? (int) $this->parameters['page'] : 1;

        $rendered = $this->template->render([
            'pending' => $this->getPendingJobs($page - 1)
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @param int $page
     *
     * @return array
     */
    private function getPendingJobs($page)
    {
        return [];

        $query = $this->em
            ->createQuery(self::DQL_ROLLBACKS)
            ->setMaxResults(self::MAX_PER_PAGE)
            ->setFirstResult(self::MAX_PER_PAGE * $page);

        return new Paginator($query);
    }
}
