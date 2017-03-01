<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\ServerValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddServerController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const SUCCESS = 'Server "%s" added.';

    private const ERR_NO_ENVIRONMENTS = 'A server requires an environment. Environments must be added before servers.';

    /**
     * @var TemplateInterface
     */
    private $template;


    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @var ServerValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param ServerValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ServerValidator $validator,
        URI $uri
    ) {
        $this->template = $template;

        $this->em = $em;
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$environments = $this->environmentRepo->getAllEnvironmentsSorted()) {
            $this->withFlash($request, Flash::ERROR, self::ERR_NO_ENVIRONMENTS);

            return $response;
        }

        if ($server = $this->handleForm($request)) {
            $this->withFlash($request, Flash::SUCCESS, sprintf(self::SUCCESS, $server->name()));
            return $this->withRedirectRoute($response, $this->uri, 'servers');
        }

        $form = $this->getFormData($request);

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),
            'environments' => $environments
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return null|Server
     */
    private function handleForm(ServerRequestInterface $request)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $data = $this->getFormData($request);

        $server = $this->validator->isValid(
            $data['server_type'],
            $data['environment'],
            $data['hostname'],
            $data['region']
        );

        if ($server) {
            // persist to database
            $this->em->merge($server);
            $this->em->flush();
        }

        return $server;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request): array
    {
        $form = [
            'server_type' => $request->getParsedBody()['server_type'] ?? '',
            'environment' => $request->getParsedBody()['environment'] ?? '',

            'hostname' => trim($request->getParsedBody()['hostname'] ?? ''),
            'region' => trim($request->getParsedBody()['region'] ?? '')
        ];

        return $form;
    }
}
