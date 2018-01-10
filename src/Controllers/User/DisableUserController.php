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
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class DisableUserController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_ENABLED = 'User %s enabled. This user can now sign in.';
    private const MSG_DISABLED = 'User %s disabled. This user can no longer sign in to Hal.';

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

        $route = $request
            ->getAttribute('route')
            ->getName();

        $toDisable = ($route === 'user.disable');
        $currentDisabled = ($user->isDisabled() === true);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, self::ERR_CSRF_OR_STATE);
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
        }

        if ($toDisable === $currentDisabled) {
            $this->withFlashError($request, self::ERR_CSRF_OR_STATE);
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
        }

        $user->withIsDisabled(!$currentDisabled);

        $this->em->merge($user);
        $this->em->flush();

        if ($toDisable) {
            $tpl = self::MSG_DISABLED;
        } else {
            $tpl = self::MSG_ENABLED;
        }

        $this->withFlashSuccess($request, sprintf($tpl, $user->name()));

        return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
    }
}
