<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\VCS;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\VersionControlProviderValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddVersionControlController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Version Control Provider "%s" added.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var VersionControlProviderValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param VersionControlProviderValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        VersionControlProviderValidator $validator,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->validator = $validator;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $form = $this->validator->getFormData($request, null);

        if ($environment = $this->handleForm($form, $request)) {
            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $environment->name()));
            return $this->withRedirectRoute($response, $this->uri, 'vcs_providers');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'vcs_types' => VCSProviderEnum::options(),
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return VersionControlProvider|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?VersionControlProvider
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $vcs = $this->validator->isValid($data['vcs_type'], $data);

        if ($vcs) {
            $this->em->persist($vcs);
            $this->em->flush();
        }

        return $vcs;
    }
}
