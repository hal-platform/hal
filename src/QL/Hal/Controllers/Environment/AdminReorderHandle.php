<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Session;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminReorderHandle
{
    /**
     *  @var EnvironmentRepository
     */
    private $envRepo;

    /**
     *  @var EntityManager
     */
    private $entityManager;

    /**
     *  @var Session
     */
    private $session;

    /**
     *  @var UrlHelper
     */
    private $url;

    /**
     *  @param EnvironmentRepository $envRepo
     *  @param EntityManager $entityManager
     *  @param Session $session
     *  @param UrlHelper $url
     */
    public function __construct(
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->envRepo = $envRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$environments = $this->envRepo->findAll()) {
            return $notFound();
        }

        $ordered = [];
        foreach ($request->post() as $postKey => $selectedOrder) {
            if (substr($postKey, 0, 3) !== 'env') {
                continue;
            }

            $id = (int) substr($postKey, 3);
            if ($id === 0) {
                continue;
            }

            $selectedOrder = (int) $selectedOrder;
            $this->insertIntoOpenPosition($id, $selectedOrder, $ordered);
        }

        // collapse the order in case some positions were skipped, and re-index on 1
        $ordered = array_merge(['prefix'], array_values($ordered));
        unset($ordered[0]);

        // save the new orders
        $ordered = array_flip($ordered);
        foreach ($environments as $environment) {
            $id = $environment->getId();
            if (!isset($ordered[$id])) {
                $this->session->addFlash('An environment is missing from the new ordering.', 'reorder-error');
                return $this->url->redirectFor('environment.admin.reorder');
            }

            $environment->setOrder($ordered[$id]);
            $this->entityManager->merge($environment);
        }

        // persist and redirect
        $this->entityManager->flush();
        $this->session->addFlash('New environment orders saved!', 'reorder-success');
        $this->url->redirectFor('environments');
    }

    /**
     * @param string $id
     * @param int $selectedOrder
     * @param array $data
     * @return null
     */
    private function insertIntoOpenPosition($id, $selectedOrder, &$data)
    {
        // take the position
        if (!isset($data[$selectedOrder])) {
            $data[$selectedOrder] = $id;
            return;
        }

        // the next position is available, so take it
        $newOrder = $selectedOrder + 1;
        if (!isset($data[$newOrder])) {
            $data[$newOrder] = $id;
            return;
        }

        // Crap, now we have a non-simple position resolution

        // find the number of elements before the collision
        $offset = 0;
        foreach ($data as $order => $k) {
            $offset++;
            if ($order === $newOrder) {
                break;
            }
        }

        $size = count($data) - $offset;

        // slice off the back after the collision
        $sliced = array_slice($data, ($size * -1), null, true);
        $data = array_slice($data, 0, $offset, true);
        $data[$newOrder + 1] = $id;

        // insert the post-collisions recursively, so any new collisions can be resolved
        foreach ($sliced as $order => $k) {
            $this->insertIntoOpenPosition($k, $order, $data);
        }
    }
}
