<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\TargetValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class EditTargetMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'Target updated.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TargetValidator
     */
    private $validator;

    /**
     * @param EntityManagerInterface $em
     * @param TargetValidator $validator
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

        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);
        $form = $this->getFormData($request);

        $target = $this->validator->isEditValid($target, ...array_values($form));

        if (!$target) {
            return $next(
                $this->withContext($request, ['errors' => $this->validator->errors()]),
                $response
            );
        }

        $this->em->persist($target);
        $this->em->flush();

        $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
        return $this->withRedirectRoute($response, $this->uri, 'target', ['application' => $application->id(), 'target' => $target->id()]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $form = [
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
