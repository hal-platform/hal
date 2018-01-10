<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\Token;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserToken;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\GUID;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class AddTokenController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use ValidatorTrait;

    private const MSG_SUCCESS = 'Token "%s" created successfully.';

    private const ERR_NAME_REQUIRED = 'Token name is required to create a token.';
    private const ERR_CSRF_OR_STATE = 'An error occurred. Please try again.';

    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, self::ERR_CSRF_OR_STATE);
            return $this->withRedirectRoute($response, $this->uri, 'settings');
        }

        if (!$token = $this->handleForm($user, $request)) {
            $this->withFlashError($request, self::ERR_NAME_REQUIRED);
            return $this->withRedirectRoute($response, $this->uri, 'settings');
        }

        $this->em->persist($token);
        $this->em->merge($user);

        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $token->name()));
        return $this->withRedirectRoute($response, $this->uri, 'settings');
    }

    /**
     * @param User $user
     * @param ServerRequestInterface $request
     *
     * @return UserToken|null
     */
    private function handleForm(User $user, ServerRequestInterface $request): ?UserToken
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $name = $request->getParsedBody()['name'] ?? '';

        if (!$this->validateLength($name, 5, 100)) {
            return null;
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_WHITESPACE)) {
            return null;
        }

        return $this->generateToken($user, $name);
    }

    /**
     * @param User $user
     * @param string $name
     *
     * @return UserToken
     */
    private function generateToken(User $user, $name): UserToken
    {
        // @todo encrypt
        $secret = GUID::create()->format(GUID::STANDARD | GUID::HYPHENATED);

        $token = (new UserToken)
            ->withName($name)
            ->withValue($secret)
            ->withUser($user);

        $user
            ->tokens()
            ->add($token);

        return $token;
    }
}
