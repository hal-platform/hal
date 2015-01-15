<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Validator\DeploymentValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Slim\Halt;
use QL\Panthor\Utility\Json;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

class AddDeploymentJsonHandler implements MiddlewareInterface
{
    const ERR_UNKNOWN = 'An unknown error occured.';
    const ERR_INVALID_JSON = 'Invalid JSON provided.';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type DeploymentValidator
     */
    private $validator;

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
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManager $em
     * @param DeploymentValidator $validator
     * @param Halt $halt
     * @param Json $json
     * @param Url $url
     * @param Request $request
     * @param Response $response
     * @param array $parameters
     */
    public function __construct(
        EntityManager $em,
        DeploymentValidator $validator,
        Halt $halt,
        Json $json,
        Url $url,
        Request $request,
        Response $response,
        array $parameters
    ) {
        $this->em = $em;
        $this->validator = $validator;

        $this->halt = $halt;
        $this->json = $json;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->response->headers->set('Content-Type', 'application/json');

        // deployment always returned, it will blow up if not.
        $deployment = $this->handleJson($this->parameters['repository']);

        // if validator didn't create a deployment, pass through to controller to handle errors
        if (!$deployment) {
            return;
        }

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        // redirect to api endpoint for resource
        $this->url->redirectFor('api.deployment', ['id' => $deployment->getId()]);
    }

    /**
     * @param int $repositoryId
     *
     * @return Deployment|null
     */
    private function handleJson($repositoryId)
    {
        $body = $this->request->getBody();
        $decoded = call_user_func($this->json, $body);

        // the json was not in the form we expected
        if (!is_array($decoded)) {
            return $this->jsonExploder([self::ERR_INVALID_JSON]);
        }

        $deployment = $this->validator->isValid(
            $repositoryId,
            isset($decoded['server']) ? $decoded['server'] : null,
            isset($decoded['path']) ? $decoded['path'] : null,
            isset($decoded['ebs_environment']) ? $decoded['ebs_environment'] : null,
            isset($decoded['url']) ? $decoded['url'] : null
        );

        // validator errors
        if (!$deployment) {
            return $this->jsonExploder($this->validator->errors());
        }

        return $deployment;
    }

    /**
     * @param array $errors
     *
     * @return null
     */
    private function jsonExploder(array $errors)
    {
        // if empty for some reason, use a default error
        if (!$errors) {
            $errors = [self::ERR_UNKNOWN];
        }

        $response = [
            'errors' => $errors
        ];

        $json = $this->json->encode($response);

        call_user_func($this->halt, 400, $json);

        return null;
    }
}
