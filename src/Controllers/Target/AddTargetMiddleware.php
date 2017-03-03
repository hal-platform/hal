<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\TargetValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

class AddTargetMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use APITrait, TemplatedControllerTrait {
        APITrait::withNewBody insteadof TemplatedControllerTrait;
    }

    const MSG_SUCCESS = 'Deployment target added.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TargetValidator
     */
    private $validator;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param TargetValidator $validator
     * @param JSON $json
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        TargetValidator $validator,
        JSON $json,
        URI $uri
    ) {
        $this->em = $em;

        $this->validator = $validator;
        $this->json = $json;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        $application = $request->getAttribute(Application::class);
        $form = $this->getFormData($request);

        $target = $this->validator->isValid($application, ...array_values($form));

        if (!$target) {
            if ($this->isXHR($request)) {
                return $this
                    ->withNewBody($response, $this->json->encode(['errors' => $this->validator->errors()]))
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);

            } else {
                return $next(
                    $this->withContext($request, ['errors' => $this->validator->errors()]),
                    $response
                );
            }
        }

        // persist to database
        $this->em->persist($target);
        $this->em->flush();

        // Clear cached query for buildable environments
        $envRepo = $this->em
            ->getRepository(Environment::class)
            ->clearBuildableEnvironmentsByApplication($application);

        if ($this->isXHR($request)) {
            return $this->withRedirectRoute($response, $this->uri, 'api.target', ['target' => $target->id()]);
        } else {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $form = [
            'server' => $request->getParsedBody()['server'] ?? '',

            'name' => $request->getParsedBody()['name'] ?? '',
            'path' => $request->getParsedBody()['path'] ?? '',

            'cd_name' => $request->getParsedBody()['cd_name'] ?? '',
            'cd_group' => $request->getParsedBody()['cd_group'] ?? '',
            'cd_config' => $request->getParsedBody()['cd_config'] ?? '',

            'eb_name' => $request->getParsedBody()['eb_name'] ?? '',
            'eb_environment' => $request->getParsedBody()['eb_environment'] ?? '',

            's3_bucket' => $request->getParsedBody()['s3_bucket'] ?? '',
            's3_file' => $request->getParsedBody()['s3_file'] ?? '',

            'script_context' => $request->getParsedBody()['script_context'] ?? '',

            'url' => $request->getParsedBody()['url'] ?? ''
            // 'credential' => $request->getParsedBody()['credential'] ?? ''
        ];

        return $form;
    }
}
