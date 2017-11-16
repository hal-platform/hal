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

class EditServerController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Group updated successfully.';

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
     * EditServerController constructor.
     *
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
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $server = $request->getAttribute(Server::class);

        $form = $this->getFormData($request, $server);

        if ($modified = $this->handleForm($form, $request, $server)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'server', ['server' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'server' => $server,
            'environments' => $this->environmentRepo->getAllEnvironmentsSorted(),
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Server $server
     *
     * @return null|Server
     */
    private function handleForm(array $data, ServerRequestInterface $request, Server $server)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $server = $this->validator->isEditValid(
            $server,
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
     * @param Server $server
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Server $server)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'server_type' => $request->getParsedBody()['server_type'] ?? '',
                'environment' => $request->getParsedBody()['environment'] ?? '',

                'hostname' => trim($request->getParsedBody()['hostname'] ?? ''),
                'region' => trim($request->getParsedBody()['region'] ?? '')
            ];
        } else {
            $form = [
                'server_type' => $server->type(),
                'environment' => $server->environment()->id(),

                'hostname' => $server->name(),
                'region' => $server->name(),
            ];
        }

        return $form;
    }
}
