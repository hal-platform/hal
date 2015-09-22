<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Repository\DeploymentPoolRepository;
use QL\Hal\Flasher;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Slim\Halt;
use QL\Panthor\Utility\Json;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

class AddHandler implements MiddlewareInterface
{
    use ServerFormatterTrait;

    const SUCCESS = 'Deployment "%s" added.';
    const ERROR = 'Deployment could not be attached.';

    const ERR_MISSING = 'Deployment must be specified.';
    const ERR_NOT_FOUND = 'Deployment not found.';
    const ERR_DUPE = 'Deployment already attached.';
    const ERR_DUPE_POOL = 'Deployment already attached to another pool.';

    const ERR_UNKNOWN = 'An unknown error occured.';
    const ERR_INVALID_JSON = 'Invalid JSON provided.';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Halt
     */
    private $halt;

    /**
     * @type Json
     */
    private $json;
    /**
     * @type Url
     */
    private $url;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @type DeploymentPoolRepository
     */
    private $poolRepo;

    /**
     * @type DeploymentView
     */
    private $view;

    /**
     * @type DeploymentPool
     */
    private $pool;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param Response $response
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Halt $halt
     * @param Json $json
     * @param Url $url
     * @param DeploymentView $view
     * @param DeploymentPool $pool
     */
    public function __construct(
        Request $request,
        Response $response,
        Flasher $flasher,
        Halt $halt,
        Json $json,
        Url $url,
        EntityManagerInterface $em,
        DeploymentView $view,
        DeploymentPool $pool
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->flasher = $flasher;
        $this->halt = $halt;
        $this->json = $json;
        $this->url = $url;

        $this->em = $em;
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->poolRepo = $em->getRepository(DeploymentPool::CLASS);

        $this->view = $view;
        $this->pool = $pool;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $isAjax = ($this->request->getMediaType() === 'application/json');

        if ($isAjax) {
            return $this->handleJSONForm();
        }

        $this->handleStandardForm();
    }

    /**
     * @return void
     */
    private function handleJSONForm()
    {
        $form = $this->data(true);

        if ($deployment = $this->handleForm($form)) {
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->setBody($this->json->encode([
                'deployment' => $deployment,
                'server' => $deployment->server(),
                'remove_url' => $this->url->urlFor('pool.deployment.remove', [
                    'view' => $this->view->id(),
                    'pool' => $this->pool->id(),
                    'deployment' => $deployment->id()
                ])
            ]));

        } else {
            $this->JSONExploder($this->errors);
        }
    }

    /**
     * @return void
     */
    private function handleStandardForm()
    {
        $form = $this->data(false);

        if ($deployment = $this->handleForm($form)) {

            $name = $this->formatServerType($deployment->server());

            $message = sprintf(self::SUCCESS, $name);
            $this->flasher->withFlash($message, 'success');

        } else {
            $detail = implode(' ', $this->errors);
            $this->flasher->withFlash(self::ERROR, 'error', $detail);
        }

        $this->flasher->load('deployment_view', ['view' => $this->view->id()]);
    }

    /**
     * @param array $data
     *
     * @return Deployment|null
     */
    private function handleForm(array $data)
    {
        if (!$data['deployment']) {
            $this->errors[] = self::ERR_MISSING;
        }

        if ($this->errors) return;

        $deployment = $this->deploymentRepo->find($data['deployment']);
        if (!$deployment) {
            $this->errors[] = self::ERR_NOT_FOUND;
        }

        if ($this->errors) return;

        // local dupe
        if ($this->pool->deployments()->contains($deployment)) {
            $this->errors[] = self::ERR_DUPE;
        }

        if ($this->errors) return;

        // foreign dupe
        $dupe = $this->poolRepo->getPoolForViewAndDeployment($this->view, $deployment);
        if ($dupe) {
            $this->errors[] = self::ERR_DUPE_POOL;
        }

        if ($this->errors) return;

        $this->pool->deployments()->add($deployment);
        $this->em->merge($this->pool);
        $this->em->flush();

        return $deployment;
    }

    /**
     * @param bool $isAjax
     *
     * @return array
     */
    private function data($isAjax)
    {
        if ($isAjax) {
            return $this->decodeJSON();

        } else {

            $form = [
                'deployment' => $this->request->post('deployment')
            ];
        }

        return $form;
    }

    /**
     * @return array
     */
    private function decodeJSON()
    {
        $body = $this->request->getBody();
        $decoded = call_user_func($this->json, $body);

        // the json was not in the form we expected
        if (!is_array($decoded)) {
            return $this->JSONExploder([self::ERR_INVALID_JSON]);
        }

        return [
            'deployment' => isset($decoded['deployment']) ? $decoded['deployment'] : ''
        ];
    }

    /**
     * @param array $errors
     *
     * @return void
     */
    private function JSONExploder(array $errors)
    {
        // if empty for some reason, use a default error
        if (!$errors) {
            $errors = [self::ERR_UNKNOWN];
        }

        $response = $this->json->encode([
            'errors' => $errors
        ]);

        $this->response->headers->set('Content-Type', 'application/json');
        call_user_func($this->halt, 400, $response);
    }
}
