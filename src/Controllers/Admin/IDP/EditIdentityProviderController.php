<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\IDP;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\UserIdentityProviderValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditIdentityProviderController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Identity Provider "%s" was updated.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserIdentityProviderValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param UserIdentityProviderValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        UserIdentityProviderValidator $validator,
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
        $idp = $request->getAttribute(UserIdentityProvider::class);

        $form = $this->validator->getFormData($request, $idp);

        if ($modified = $this->handleForm($form, $request, $idp)) {
            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $idp->name()));
            return $this->withRedirectRoute($response, $this->uri, 'id_provider', ['system_idp' => $idp->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'idp' => $idp,
            'idp_types' => IdentityProviderEnum::options(),
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param UserIdentityProvider $idp
     *
     * @return UserIdentityProvider|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, UserIdentityProvider $idp): ?UserIdentityProvider
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $idp = $this->validator->isEditValid($idp, $data);

        if ($idp) {
            $this->em->merge($idp);
            $this->em->flush();
        }

        return $idp;
    }
}
