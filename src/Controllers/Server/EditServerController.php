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
use Hal\UI\Utility\Psr7HelperTrait;
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
    use Psr7HelperTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const SUCCESS = 'Server updated successfully.';

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
            $this
                ->getFlash($request)
                ->withMessage(Flash::SUCCESS, self::SUCCESS);

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
                'server_type' => $this->getParsedBodyParam($request, 'server_type'),
                'environment' => $this->getParsedBodyParam($request, 'environment'),

                'hostname' => trim($this->getParsedBodyParam($request, 'hostname')),
                'region' => trim($this->getParsedBodyParam($request, 'region'))
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
