<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\TargetValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class AddTargetMiddleware implements MiddlewareInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Deployment target added.';
    private const MSG_MORE_LIKE_THIS = <<<'HTML'
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
     * @var EnvironmentRepository
     */
    private $environmentRepo;

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
        $this->environmentRepo = $this->em->getRepository(Environment::class);

        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $application = $request->getAttribute(Application::class);
        $form = $this->getFormData($request);

        $context = ['form' => $form];

        if (!$selectedEnvironment = $this->getSelectedEnvironment($request)) {
            return $next($request, $response);
        }

        $context['selected_environment'] = $selectedEnvironment;
        $request = $request->withAttribute('selected_environment', $selectedEnvironment);

        if ($request->getMethod() !== 'POST') {
            $request = $this->withContext($request, $context);
            return $next($request, $response);
        }

        if (!$this->isCSRFValid($request)) {
            $request = $this->withContext($request, $context);
            return $next($request, $response);
        }

        $target = $this->validator->isValid($application, $selectedEnvironment, $form['deployment_type'], $form);

        if (!$target) {
            $request = $this->withContext($request, $context + ['errors' => $this->validator->errors()]);
            return $next($request, $response);
        }

        $this->em->persist($target);
        $this->em->flush();

        // Clear cached query for buildable environments
        $this->environmentRepo->clearBuildableEnvironmentsByApplication($application);

        $formPage = $this->uri->uriFor('target.add', ['application' => $application->id()], ['environment' => $target->environment()->id()]);

        $this->withFlashSuccess($request, self::MSG_SUCCESS, sprintf(self::MSG_MORE_LIKE_THIS, $formPage));
        return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();
        $type = $data['deployment_type'] ?? '';

        $form = [
            'deployment_type' => $type,
            'template' => $data['template'] ?? '',

            'name' => $data['name'] ?? '',
            'url' => $data['url'] ?? '',

            'script_context' => $data['script_context'] ?? '',
            'credential' => $data['credential'] ?? ''
        ];

        return $form + $this->validator->getTargetFormData($request, $type, null);
    }

    /**
     * @param mixed $selected
     *
     * @return Environment|null
     */
    private function getSelectedEnvironment(ServerRequestInterface $request)
    {
        $selected = $request->getQueryParams()['environment'] ?? '';

        if (!$selected) {
            return null;
        }

        $env = $this->environmentRepo->find($selected);
        if ($env) {
            return $env;
        }

        return null;
    }
}
