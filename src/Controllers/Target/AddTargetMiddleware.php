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
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class AddTargetMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use APITrait, TemplatedControllerTrait {
        APITrait::withNewBody insteadof TemplatedControllerTrait;
    }

    const MSG_SUCCESS = 'Deployment target added.';
    const MSG_MORE_LIKE_THIS = <<<'HTML'
Add more like this? <a href="%s">Continue adding deployment targets.</a>
HTML;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TargetValidator
     */
    private $validator;

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
        URI $uri
    ) {
        $this->em = $em;

        $this->validator = $validator;
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

        $selected = $request->getQueryParams();
        $application = $request->getAttribute(Application::class);
        $form = $this->getFormData($request);

        $target = $this->validator->isValid($application, ...array_values($form));

        if (!$target) {
            return $next(
                $this->withContext($request, ['errors' => $this->validator->errors()]),
                $response
            );
        }

        // persist to database
        $this->em->persist($target);
        $this->em->flush();

        // Clear cached query for buildable environments
        $envRepo = $this->em
            ->getRepository(Environment::class)
            ->clearBuildableEnvironmentsByApplication($application);

        $formPage = $this->uri->uriFor(
            'target.add',
            ['application' => $application->id()],
            ['environment' => $target->group()->environment()->id()]
        );

        $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS, sprintf(self::MSG_MORE_LIKE_THIS, $formPage));
        return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $form = [
            'group' => $request->getParsedBody()['group'] ?? '',

            'name' => $request->getParsedBody()['name'] ?? '',
            'path' => $request->getParsedBody()['path'] ?? '',

            'cd_name' => $request->getParsedBody()['cd_name'] ?? '',
            'cd_group' => $request->getParsedBody()['cd_group'] ?? '',
            'cd_config' => $request->getParsedBody()['cd_config'] ?? '',

            'eb_name' => $request->getParsedBody()['eb_name'] ?? '',
            'eb_environment' => $request->getParsedBody()['eb_environment'] ?? '',

            's3_method' => $request->getParsedBody()['s3_method'] ?? '',
            's3_bucket' => $request->getParsedBody()['s3_bucket'] ?? '',
            's3_local_path' => $request->getParsedBody()['s3_local_path'] ?? '',
            's3_remote_path' => $request->getParsedBody()['s3_remote_path'] ?? '',

            'script_context' => $request->getParsedBody()['script_context'] ?? '',

            'url' => $request->getParsedBody()['url'] ?? '',
            'credential' => $request->getParsedBody()['credential'] ?? ''
        ];

        return $form;
    }
}
