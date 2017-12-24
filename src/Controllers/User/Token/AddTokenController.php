<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\Token;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserToken;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class AddTokenController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Token "%s" created successfully.';
    const ERR_NAME_REQUIRED = 'Token name is required to create a token.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var callable
     */
    private $random;

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

        $name = $request->getParsedBody()['name'] ?? '';

        if (!$name) {
            $this->withFlash($request, Flash::ERROR, self::ERR_NAME_REQUIRED);
            return $this->withRedirectRoute($response, $this->uri, 'settings');
        }

        $token = $this->generateToken($user, $name);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $token->name()));
        return $this->withRedirectRoute($response, $this->uri, 'settings');
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
        $secret = bin2hex(random_bytes(20));

        $token = (new UserToken)
            ->withName($name)
            ->withValue($secret)
            ->withUser($user);

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }
}
