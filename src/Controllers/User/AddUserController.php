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
use Hal\Core\Entity\User\UserIdentity;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Parameters;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\UserValidator;
use Hal\UI\Validator\UserIdentityValidator;
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
<input type="text" style="color:black;" value="%s" readonly>
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
    private $idpRepo;

    /**
     * @var UserValidator
     */
    private $userValidator;

    /**
     * @var UserIdentityValidator
     */
    private $identityValidator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param UserValidator $userValidator
     * @param UserIdentityValidator $identityValidator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        UserValidator $userValidator,
        UserIdentityValidator $identityValidator,
        URI $uri
    ) {
        $this->template = $template;

        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);
        $this->em = $em;

        $this->userValidator = $userValidator;
        $this->identityValidator = $identityValidator;
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
            $identity = $user->identities()->first();
            return $this->sendSuccessInstructions($identity, $request, $response);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => array_merge($this->userValidator->errors(), $this->identityValidator->errors()),

            'id_providers' => $this->idpRepo->findAll(),
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

        $user = $this->userValidator->isValid($data);
        $identity = $this->identityValidator->isValid($data);

        if (!$user || !$identity) {
            return null;
        }

        $identity->withUser($user);
        $user->identities()->add($identity);

        $this->em->persist($user);
        $this->em->persist($identity);
        $this->em->flush();

        return $user;
    }

    /**
     * @param UserIdentity $identity
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function sendSuccessInstructions(UserIdentity $identity, ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $identity->user();

        $setupURL = $this->uri->absoluteURIFor(
            $request->getUri(),
            'signin.setup',
            [
                'user' => $user->id(),
                'setup_token' => $identity->parameter(Parameters::ID_INTERNAL_SETUP_TOKEN),
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

            'internal_username' => $request->getParsedBody()['internal_username'] ?? '',
        ];

        return $form;
    }
}
