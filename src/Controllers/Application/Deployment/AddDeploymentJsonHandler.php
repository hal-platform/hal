<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManager;
use Hal\UI\Validator\DeploymentValidator;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
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
     * @var EntityManager
     */
    private $em;

    /**
     * @var DeploymentValidator
     */
    private $validator;

    /**
     * @var Halt
     */
    private $halt;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

    /**
     * @param EntityManager $em
     * @param DeploymentValidator $validator
     * @param Halt $halt
     * @param Json $json
     * @param Url $url
     * @param Request $request
     * @param Response $response
     * @param Application $application
     */
    public function __construct(
        EntityManager $em,
        DeploymentValidator $validator,
        Halt $halt,
        Json $json,
        Url $url,
        Request $request,
        Response $response,
        Application $application
    ) {
        $this->em = $em;
        $this->validator = $validator;

        $this->halt = $halt;
        $this->json = $json;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
        $this->application = $application;

        $this->envRepo = $em->getRepository(Environment::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->response->headers->set('Content-Type', 'application/json');

        // deployment always returned, it will blow up if not.
        $deployment = $this->handleJson();

        // if validator didn't create a deployment, pass through to controller to handle errors
        if (!$deployment) {
            return;
        }

        // Clear cached query for buildable environments
        $this->envRepo->clearBuildableEnvironmentsByApplication($deployment->application());

        // persist to database
        $this->em->persist($deployment);
        $this->em->flush();

        // redirect to api endpoint for resource
        $this->url->redirectFor('api.deployment', ['id' => $deployment->id()]);
    }

    /**
     * @return Deployment|null
     */
    private function handleJson()
    {
        $body = $this->request->getBody();
        $decoded = call_user_func($this->json, $body);

        // the json was not in the form we expected
        if (!is_array($decoded)) {
            return $this->jsonExploder([self::ERR_INVALID_JSON]);
        }

        $deployment = $this->validator->isValid(
            $this->application,
            isset($decoded['server']) ? $decoded['server'] : null,
            isset($decoded['name']) ? $decoded['name'] : '',
            isset($decoded['path']) ? $decoded['path'] : null,

            isset($decoded['cd_name']) ? $decoded['cd_name'] : null,
            isset($decoded['cd_group']) ? $decoded['cd_group'] : null,
            isset($decoded['cd_config']) ? $decoded['cd_config'] : null,

            isset($decoded['eb_name']) ? $decoded['eb_name'] : null,
            isset($decoded['eb_environment']) ? $decoded['eb_environment'] : null,

            isset($decoded['s3_bucket']) ? $decoded['s3_bucket'] : null,
            isset($decoded['s3_file']) ? $decoded['s3_file'] : null,
            isset($decoded['s3_file']) ? $decoded['s3_file'] : null,

            isset($decoded['script_context']) ? $decoded['script_context'] : null,

            isset($decoded['url']) ? $decoded['url'] : ''
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
