<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\TargetValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class EditTargetMiddleware implements MiddlewareInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'Deployment target updated.';

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
        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);
        $form = $this->getFormData($request, $target);

        $context = ['form' => $form];

        if ($request->getMethod() !== 'POST') {
            $request = $this->withContext($request, $context);
            return $next($request, $response);
        }

        if (!$this->isCSRFValid($request)) {
            $request = $this->withContext($request, $context);
            return $next($request, $response);
        }

        $target = $this->validator->isEditValid($target, $target->environment(), $form);

        if (!$target) {
            $request = $this->withContext($request, $context + ['errors' => $this->validator->errors()]);
            return $next($request, $response);
        }

        $this->em->persist($target);
        $this->em->flush();

        $this->withFlashSuccess($request, self::MSG_SUCCESS);
        return $this->withRedirectRoute($response, $this->uri, 'target', ['application' => $application->id(), 'target' => $target->id()]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Target $target
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Target $target)
    {
        $data = $request->getParsedBody();

        if ($request->getMethod() !== 'POST') {
            $data['template'] = $target->template() ? $target->template()->id() : '';
            $data['credential'] = $target->credential() ? $target->credential()->id() : '';

            $data['name'] = $target->name();
            $data['url'] = $target->url();
            $data['script_context'] = $target->parameter(Target::PARAM_CONTEXT);
        }

        $type = $target->type();

        $form = [
            'deployment_type' => $type,
            'template' => $data['template'] ?? '',

            'name' => $data['name'] ?? '',
            'url' => $data['url'] ?? '',

            'script_context' => $data['script_context'] ?? '',
            'credential' => $data['credential'] ?? ''
        ];

        return $form + $this->validator->getTargetFormData($request, $type, $target);
    }
}
