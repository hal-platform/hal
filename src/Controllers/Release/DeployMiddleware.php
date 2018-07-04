<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\Target;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\Validator\MetaValidator;
use Hal\UI\Validator\ReleaseValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class DeployMiddleware implements MiddlewareInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    public const SELECTED_ENVIRONMENT_ATTRIBUTE = 'selected_environment';

    private const WAIT_FOR_IT = "The release has been queued and will be deployed shortly.";

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ReleaseValidator
     */
    private $validator;

    /**
     * @var MetaValidator
     */
    private $metaValidator;

    /**
     * @var EntityRepository
     */
    private $environmentRepo;
    private $targetRepo;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param ReleaseValidator $validator
     * @param MetaValidator $metaValidator
     * @param StickyEnvironmentService $stickyService
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        ReleaseValidator $validator,
        MetaValidator $metaValidator,
        StickyEnvironmentService $stickyService,
        URI $uri
    ) {
        $this->environmentRepo = $em->getRepository(Environment::class);
        $this->targetRepo = $em->getRepository(Target::class);
        $this->em = $em;

        $this->validator = $validator;
        $this->metaValidator = $metaValidator;
        $this->stickyService = $stickyService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $build = $request->getAttribute(Build::class);
        $form = $this->getFormData($request);

        $context = ['form' => $form];

        if (!$selectedEnvironment = $this->getSelectedEnvironment($request, $build)) {
            return $next($request, $response);
        }

        $context[self::SELECTED_ENVIRONMENT_ATTRIBUTE] = $selectedEnvironment;
        $request = $request->withAttribute(self::SELECTED_ENVIRONMENT_ATTRIBUTE, $selectedEnvironment);

        if ($request->getMethod() !== 'POST') {
            $request = $this->withContext($request, $context);
            return $next($request, $response);
        }

        if (!$this->isCSRFValid($request)) {
            $request = $this->withContext($request, $context);
            return $next($request, $response);
        }

        $user = $this->getUser($request);
        $application = $build->application();

        $releases = $this->validator->isValid($application, $user, $selectedEnvironment, $build, $form['targets']);

        // Pass through to controller if errors
        if (!$releases) {
            $request = $this->withContext($request, $context + ['errors' => $this->validator->errors()]);
            return $next($request, $response);
        }

        if (!$this->saveMetadata($releases, $request)) {
            $request = $this->withContext($request, $context + ['errors' => $this->metaValidator->errors()]);
            return $next($request, $response);
        }

        $this->saveChanges($releases);

        // override sticky environment
        $response = $this->stickyService->save($request, $response, $application->id(), $selectedEnvironment->id());

        $this->withFlashSuccess($request, self::WAIT_FOR_IT);
        return $this->withRedirectRoute($response, $this->uri, 'application.dashboard', ['application' => $application->id()]);
    }

    /**
     * @param array $releases
     *
     * @return array
     */
    private function saveChanges(array $releases)
    {
        foreach ($releases as $release) {
            // record releases as active job on each target
            $target = $release->target();
            $target->withLastJob($release);

            $this->em->persist($target);
            $this->em->persist($release);
        }

        $this->em->flush();
    }

    /**
     * @param array $releases
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function saveMetadata(array $releases, ServerRequestInterface $request)
    {
        $names = $request->getParsedBody()['metadata_names'] ?? [];
        $values = $request->getParsedBody()['metadata_values'] ?? [];

        $names = is_array($names) ? $names : [];
        $values = is_array($values) ? $values : [];

        if (!$names && !$values) {
            return true;
        }

        $metadatas = [];
        foreach ($names as $index => $name) {
            if ($name && isset($values[$index]) && strlen($values[$index]) > 0) {
                $metadatas[$name] = $values[$index];
            }
        }

        if (!$metadatas) {
            return true;
        }

        $metas = [];

        foreach ($releases as $release) {
            if ($m = $this->metaValidator->isBulkValid($release, $metadatas)) {
                $metas = array_merge($metas, $m);
            } else {
                return false;
            }
        }

        foreach ($metas as $meta) {
            $this->em->persist($meta);
        }

        return true;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        // load selected target, but only if fresh form and not posted
        if ($request->getMethod() !== 'POST') {
            $selected = $request->getQueryParams()['target'] ?? '';
            $data['targets'] = $selected ? [$selected] : [];
        }

        $form = [
            'targets' => $data['targets'] ?? [],

            'metadata_names' => $data['metadata_names'] ?? [],
            'metadata_values' => $data['metadata_values'] ?? [],
        ];

        return $form;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Build $build
     *
     * @return Environment|null
     */
    private function getSelectedEnvironment(ServerRequestInterface $request, Build $build)
    {
        if ($build->environment()) {
            return $build->environment();
        }

        $queryString = $request->getQueryParams();

        // used for rollbacks
        $targetID = $queryString['target'] ?? '';

        // used for env picker
        $environmentID = $queryString['environment'] ?? '';

        if ($targetID && $target = $this->targetRepo->find($targetID)) {
            if ($target instanceof Target) {
                return $target->environment();
            }
        }

        if ($environmentID && $environment = $this->environmentRepo->find($environmentID)) {
            if ($environment instanceof Environment) {
                return $environment;
            }
        }

        return null;
    }
}
