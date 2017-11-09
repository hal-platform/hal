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

class EditGroupController implements ControllerInterface
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
     * @var GroupValidator
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
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $group = $request->getAttribute(Group::class);

        $form = $this->getFormData($request, $group);

        if ($modified = $this->handleForm($form, $request, $group)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'group', ['group' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'group' => $group,
            'group_types' => GroupEnum::options(),
            'environments' => $this->environmentRepo->getAllEnvironmentsSorted(),
            'aws_regions' => AWSAuthenticator::$awsRegions
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Group $group
     *
     * @return null|Group
     */
    private function handleForm(array $data, ServerRequestInterface $request, Group $group)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $group = $this->validator->isEditValid(
            $group,
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
     * @param Group $group
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Group $group)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'group_type' => $request->getParsedBody()['group_type'] ?? '',
                'environment' => $request->getParsedBody()['environment'] ?? '',

                'hostname' => trim($request->getParsedBody()['hostname'] ?? ''),
                'region' => trim($request->getParsedBody()['region'] ?? '')
            ];
        } else {
            $form = [
                'group_type' => $group->type(),
                'environment' => $group->environment()->id(),

                'hostname' => $group->name(),
                'region' => $group->name(),
            ];
        }

        return $form;
    }
}
