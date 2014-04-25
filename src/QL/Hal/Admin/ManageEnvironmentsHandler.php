<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageEnvironmentsHandler
{
    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Twig_Template $tpl
     * @param EnvironmentService $envService
     */
    public function __construct(Twig_Template $tpl, EnvironmentService $envService)
    {
        $this->tpl = $tpl;
        $this->envService = $envService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        if ($request->get('reorder') === '1') {
            return $this->handleEnvironmentReordering($request, $response);
        }

        return $this->handleEnvironmentCreation($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    public function goAway(Request $request, Response $response)
    {
        $response->status(303);
        $response->header('Location', $request->getScheme() . '://' . $request->getHostWithPort() . '/admin/envs');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    private function handleEnvironmentCreation(Request $request, Response $response)
    {
        $envname = $request->post('envname');

        $errors = [];
        if ($this->validateEnvName($envname, $errors)) {
            $response->body($this->tpl->render([
                'errors' => $errors,
                'cur_env' => $envname,
                'envs' => $this->envService->listAll()
            ]));

            return;
        }

        $this->envService->create(strtolower($envname));
        $this->goAway($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    private function handleEnvironmentReordering(Request $request, Response $response)
    {
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

        $this->envService->updateOrder($ordered);
        $this->goAway($request, $response);
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

    /**
     * @param string $name
     * @param string[] $errors
     * @return boolean
     */
    private function validateEnvName($name, array &$errors)
    {
        $newErrors = [];

        if (!preg_match('@^[a-zA-Z_-]*$@', $name)) {
            $newErrors[] = 'Environment name must consist of letters, underscores and/or hyphens.';
        }

        if (strlen($name) > 24 || strlen($name) < 2) {
            $newErrors[] = 'Environment name must be between 2 and 24 characters.';
        }

        $errors = array_merge($errors, $newErrors);
        return count($newErrors) > 0;
    }
}
