<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddTargetController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use SortingTrait;
    use TemplatedControllerTrait;

    private const ERR_NO_SERVERS = 'Targets require servers. Servers must be added before targets.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $serverRepo;

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

        $this->serverRepo = $em->getRepository(Server::class);
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
        $serversByEnv = $this->environmentalizeServers($environments);

        // If no servers, throw flash and send back to targets.
        if (!$serversByEnv) {
            $this->withFlash($request, Flash::ERROR, self::ERR_NO_SERVERS);
            return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
        }

        $form = $this->getFormData($request);

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,

            'application' => $application,
            'servers_by_env' => $serversByEnv
        ]);
    }


    /**
     * @param Environment[] $environments
     *
     * @return array
     */
    private function environmentalizeServers(array $environments)
    {
        $servers = $this->serverRepo->findAll();

        $env = [];
        foreach ($environments as $environment) {
            $env[$environment->name()] = [];
        }

        $environments = $env;

        foreach ($servers as $server) {
            $env = $server->environment()->name();

            if (!array_key_exists($env, $environments)) {
                $environments[$env] = [];
            }

            $environments[$env][] = $server;
        }

        $sorter = $this->serverSorter();
        foreach ($environments as &$env) {
            usort($env, $sorter);
        }

        foreach ($environments as $key => $servers) {
            if (count($servers) === 0) {
                unset($environments[$key]);
            }
        }

        return $environments;
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
