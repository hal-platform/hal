<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;
use function password_hash;

class SignInSetupController implements ControllerInterface
{
    use CSRFTrait;
    use SessionTrait;
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Password saved. Please sign in using your credentials.';

    const ERR_INVALID = 'Invalid user specified. Your token may have expired.';
    const ERR_PASSWORD_MATCH = 'Entered password do not match. Please try again.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Clock $clock
     * @param URI $uri
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, Clock $clock, URI $uri)
    {
        $this->template = $template;
        $this->em = $em;

        $this->clock = $clock;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);
        $token = $request
            ->getAttribute('route')
            ->getArgument('setup_token');

        if ($user->provider()->type() !== IdentityProviderEnum::TYPE_INTERNAL) {
            // Only internal supported
            return $response;
        }

        $storedToken = $user->parameter('internal.setup_token');
        $expiry = $user->parameter('internal.setup_token_expiry');

        if (!$expiry || !$storedToken || !$token) {
            return $this->byebye($request, $response);
        }

        $expiry = $this->clock->fromString($expiry);
        $isExpiryValid = $expiry && $this->clock->inRange($expiry, null, '5 minutes');
        $isTokenValid = hash_equals($storedToken, $token);

        if (!$isExpiryValid || !$isTokenValid) {
            return $this->byebye($request, $response);
        }

        $form = $this->getFormData($request);

        if ($updated = $this->handleForm($user, $form, $request)) {
            $this->em->persist($updated);
            $this->em->flush();

            $this->withFlashSuccess($request, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors(),

            'user' => $user
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function byebye(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->withFlashError($request, self::ERR_INVALID);
        return $this->withRedirectRoute($response, $this->uri, 'signin');
    }

    /**
     * @param User $user
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return User|null
     */
    private function handleForm(User $user, array $data, ServerRequestInterface $request): ?User
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $this->validate($data);
        if ($this->hasErrors()) {
            return null;
        }

        $hashed = password_hash($data['new_password'], \PASSWORD_BCRYPT, [
            'cost' => 10,
        ]);

        $params = $user->parameters();
        unset($params['internal.setup_token']);
        unset($params['internal.setup_token_expiry']);
        $user
            ->withParameters($params)
            ->withParameter('internal.password', $hashed);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function validate(array $data)
    {
        $password = $data['new_password'];
        $passwordRepeat = $data['new_password_repeat'];

        if (strlen($password) === 0) {
            $this->addRequiredError('Password', 'new_password');
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (!$this->validateLength($password, 3, 200)) {
            $this->addLengthError('Password', 3, 200, 'new_password');
        }

        if ($password !== $passwordRepeat) {
            $this->addError(self::ERR_PASSWORD_MATCH, 'new_password');
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request): array
    {
        $form = [
            'new_password' => $request->getParsedBody()['new_password'] ?? '',
            'new_password_repeat' => $request->getParsedBody()['new_password_repeat'] ?? '',
        ];

        return $form;
    }
}
