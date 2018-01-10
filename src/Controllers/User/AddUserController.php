<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
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

class AddUserController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'User "%s" added.';
    private const MSG_FIRST_TIME_SIGNIN = <<<'HTML'
Please note this user cannot yet sign in. Send them to the following URL to create their password:<br>
<input class="text-input" style="color:black;" value="%s" readonly>
This link will expire in 8 hours.
HTML;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userRepo;
    private $idpRepo;

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

        $this->userRepo = $em->getRepository(User::class);
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);
        $this->em = $em;

        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);
        $form = $this->getFormData($request);

        if ($user = $this->handleForm($form, $request)) {
            return $this->sendSuccessInstructions($user, $request, $response);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'id_providers' => $this->idpRepo->findAll()
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return User|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?User
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $user = $this->validator->isValid($data);

        if ($user) {
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    /**
     * @param User $user
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function sendSuccessInstructions(User $user, ServerRequestInterface $request, ResponseInterface $response)
    {
        $setupURL = $this->uri->absoluteURIFor(
            $request->getUri(),
            'signin.setup',
            [
                'user' => $user->id(),
                'setup_token' => $user->parameter('internal.setup_token')
            ]
        );

        $alert = sprintf(self::MSG_SUCCESS, $user->name());
        $firstTimeMsg = sprintf(self::MSG_FIRST_TIME_SIGNIN, $setupURL);

        $this->withFlashSuccess($request, $alert, $firstTimeMsg);

        return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request): array
    {
        $form = [
            'name' => $request->getParsedBody()['name'] ?? '',
            'id_provider' => $request->getParsedBody()['id_provider'] ?? '',

            'internal_username' => $request->getParsedBody()['internal_username'] ?? ''
        ];

        return $form;
    }
}
