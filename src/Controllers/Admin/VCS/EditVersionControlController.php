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

class EditVersionControlController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Version Control Provider "%s" was updated.';

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
        $vcs = $request->getAttribute(VersionControlProvider::class);

        $form = $this->validator->getFormData($request, $vcs);

        if ($modified = $this->handleForm($form, $request, $vcs)) {
            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $vcs->name()));
            return $this->withRedirectRoute($response, $this->uri, 'vcs_provider', ['system_vcs' => $vcs->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'vcs' => $vcs,
            'vcs_types' => VCSProviderEnum::options(),
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param VersionControlProvider $vcs
     *
     * @return VersionControlProvider|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, VersionControlProvider $vcs): ?VersionControlProvider
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $vcs = $this->validator->isEditValid($vcs, $data);

        if ($vcs) {
            $this->em->merge($vcs);
            $this->em->flush();
        }

        return $vcs;
    }
}
