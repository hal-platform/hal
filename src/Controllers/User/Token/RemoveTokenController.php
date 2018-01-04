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
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveTokenController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Token "%s" has been revoked.';

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
        $token = $request->getAttribute(UserToken::class);

        $currentUser = $this->getUser($request);

        $this->em->remove($token);
        $this->em->flush();

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $token->name()));

        if ($user === $currentUser) {
            return $this->withRedirectRoute($response, $this->uri, 'settings');
        } else {
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
        }
    }
}
