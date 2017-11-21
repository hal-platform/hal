<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flash;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddTargetController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use SortingTrait;
    use TemplatedControllerTrait;

    private const ERR_NO_GROUPS = 'Targets require groups. Groups must be added before targets.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $groupRepo;
    private $credentialRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        URI $uri
    ) {
        $this->template = $template;

        $this->credentialRepo = $em->getRepository(Credential::class);
        $this->groupRepo = $em->getRepository(Group::class);
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $environments = $this->environmentRepo->getAllEnvironmentsSorted();
        $groups = $credentials = [];

        $selected = $request->getQueryParams()['environment'] ?? '';
        $selectedEnvironment = $this->getSelectedEnvironment($environments, $selected);

        if ($selectedEnvironment) {
            // If no groups, throw flash and send back to targets.
            if (!$groups = $this->getGroups($selectedEnvironment)) {
                $this->withFlash($request, Flash::ERROR, self::ERR_NO_GROUPS);
                return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
            }

            // @todo fix when updated to hal-core
            // $credentials = $this->credentialRepo->findBy([], ['name' => 'ASC']);
        }

        $form = $this->getFormData($request);

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,

            'environments' => $environments,
            'selected_environment' => $selectedEnvironment,

            'groups' => $groups,
            'credentials' => $credentials,

            'application' => $application
        ]);
    }

    /**
     * @param array $environments
     * @param mixed $selected
     *
     * @return Environment|null
     */
    private function getSelectedEnvironment(array $environments, $selected)
    {
        foreach ($environments as $e) {
            if ($e->id() == $selected) {
                return $e;
            }
        }

        return null;
    }

    /**
     * @param Environment $environment
     *
     * @return array
     */
    private function getGroups(Environment $environment)
    {
        $groups = $this->groupRepo->findBy(['environment' => $environment]);

        $sorter = $this->groupSorter();
        usort($groups, $sorter);

        return $groups;
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

            's3_bucket' => $request->getParsedBody()['s3_bucket'] ?? '',
            's3_file' => $request->getParsedBody()['s3_file'] ?? '',

            'script_context' => $request->getParsedBody()['script_context'] ?? '',

            'url' => $request->getParsedBody()['url'] ?? ''
            // 'credential' => $request->getParsedBody()['credential'] ?? ''
        ];

        return $form;
    }
}
