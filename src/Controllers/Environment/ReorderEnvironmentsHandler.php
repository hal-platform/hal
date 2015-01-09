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
use QL\Panthor\ControllerInterface;
use Slim\Http\Request;

class ReorderEnvironmentsHandler implements ControllerInterface
{
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
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     * @param NotFound $notFound
     */
    public function __construct(
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        Request $request,
        NotFound $notFound
    ) {
        $this->envRepo = $envRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->notFound = $notFound;
    }


    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$environments = $this->envRepo->findAll()) {
            return call_user_func($this->notFound);
        }

        $ordered = [];
        foreach ($this->request->post() as $postKey => $selectedOrder) {
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
                $this->session->flash('An environment is missing from the new ordering.', 'error');
                return $this->url->redirectFor('environment.admin.reorder');
            }

            $environment->setOrder($ordered[$id]);
            $this->entityManager->merge($environment);
        }

        // persist and redirect
        $this->entityManager->flush();
        $this->session->flash('New environment orders saved!', 'success');
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
