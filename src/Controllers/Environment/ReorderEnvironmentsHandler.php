<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use Slim\Http\Request;

class ReorderEnvironmentsHandler implements MiddlewareInterface
{
    const ERR_MISSING = 'An environment is missing from the new ordering.';

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type Context
     */
    private $context;

    /**
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     * @param NotFound $notFound
     * @param Context $context
     */
    public function __construct(
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        Request $request,
        NotFound $notFound,
        Context $context
    ) {
        $this->envRepo = $envRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->context = $context;
    }

    /**
     * Expected post payload:
     * [
     *     "env1": 1,
     *     "env100": 5,
     *     "env1234": 2
     * ]
     *
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        if (!$environments = $this->envRepo->findAll()) {
            return call_user_func($this->notFound);
        }

        $ordered = [];

        // pull out environment order from post body
        foreach ($this->request->post() as $postKey => $selectedOrder) {
            if (substr($postKey, 0, 3) === 'env') {
                $id = (int) substr($postKey, 3);
                $ordered[$id] = (int) $selectedOrder;
            }
        }

        // Sort by provided order, then grab keys to get a list of ids in sorted order in [$order => $id] format
        asort($ordered, SORT_NUMERIC);
        $ordered = array_keys($ordered);

        // re-index on 1, in [$order => $id] format
        $ordered = array_merge(['prefix'], array_values($ordered));
        unset($ordered[0]);

        // flip to [$id => $order] format
        $ordered = array_flip($ordered);

        // save the new orders
        foreach ($environments as $environment) {
            $id = $environment->getId();
            if (!isset($ordered[$id])) {
                // throw error, continue to controller
                return $this->context->addContext([
                    'errors' => [self::ERR_MISSING]
                ]);
            }

            $environment->setOrder($ordered[$id]);
            $this->entityManager->merge($environment);
        }

        // persist and redirect
        $this->entityManager->flush();
        $this->session->flash('New environment orders saved!', 'success');
        $this->url->redirectFor('environments');
    }
}
