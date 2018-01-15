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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveTokenHandler implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = 'Token "%s" has been revoked.';

    private const ERR_CSRF_OR_STATE = 'An error occurred. Please try again.';

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

        $routeParams = ($user === $currentUser) ? ['settings'] : ['user', ['user' => $user->id()]];

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, self::ERR_CSRF_OR_STATE);
            return $this->withRedirectRoute($response, $this->uri, ...$routeParams);
        }

        $this->em->remove($token);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $token->name()));

        return $this->withRedirectRoute($response, $this->uri, ...$routeParams);
    }
}
