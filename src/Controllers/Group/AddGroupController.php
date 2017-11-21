<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\GroupValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Group;
use Hal\Core\Type\GroupEnum;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\AWS\AWSAuthenticator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddGroupController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Group "%s" added.';

    private const ERR_NO_ENVIRONMENTS = 'A group requires an environment. Environments must be added before groups.';

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
     * @var GroupValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param GroupValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GroupValidator $validator,
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
            return $this->withRedirectRoute($response, $this->uri, 'groups');
        }

        $form = $this->getFormData($request);

        if ($group = $this->handleForm($form, $request)) {
            $msg = sprintf(self::MSG_SUCCESS, $group->name() ?: $group->format(true));
            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'groups');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),
            'group_types' => GroupEnum::options(),
            'environments' => $environments,
            'aws_regions' => AWSAuthenticator::$awsRegions
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return null|Group
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Group
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $group = $this->validator->isValid(
            $data['group_type'],
            $data['environment'],
            $data['hostname'],
            $data['region']
        );

        if ($group) {
            // persist to database
            $this->em->merge($group);
            $this->em->flush();
        }

        return $group;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request): array
    {
        $form = [
            'group_type' => $request->getParsedBody()['group_type'] ?? '',
            'environment' => $request->getParsedBody()['environment'] ?? '',

            'hostname' => trim($request->getParsedBody()['hostname'] ?? ''),
            'region' => trim($request->getParsedBody()['region'] ?? '')
        ];

        return $form;
    }
}
