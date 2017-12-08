<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class ToggleUserController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_ENABLED = 'User %s enabled.';
    private const MSG_DISABLED = 'User %s disabled. This user can no longer sign in to Hal.';

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

        $enable = $request->getParsedBody()['enable_user'] ?? '';
        $disable = $request->getParsedBody()['disable_user'] ?? '';

        $msg = null;
        if ($enable) {
            $user->withIsDisabled(false);
            $msg = sprintf(self::MSG_ENABLED, $user->username());

        } elseif ($disable) {
            $user->withIsDisabled(true);
            $msg = sprintf(self::MSG_DISABLED, $user->username());
        }

        $this->em->merge($user);
        $this->em->flush();

        if ($msg) {
            $this->withFlash($request, Flash::SUCCESS, $msg);
        }

        return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
    }
}
