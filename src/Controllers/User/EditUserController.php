<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\UserValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditUserController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'User "%s" was updated.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param UserValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        UserValidator $validator,
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
        $user = $request->getAttribute(User::class);

        $form = $this->validator->getFormData($request, $user);

        if ($modified = $this->handleForm($form, $request, $user)) {
            $message = sprintf(self::MSG_SUCCESS, $user->name());

            $this->withFlashSuccess($request, $message);
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'user' => $user,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param User $user
     *
     * @return User|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, User $user): ?User
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $user = $this->validator->isEditValid($user, $data);

        if ($user) {
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}
