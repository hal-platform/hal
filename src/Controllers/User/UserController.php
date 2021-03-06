<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Hal\Core\Entity\User;
use Hal\Core\Parameters;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class UserController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var AuthorizationHydrator
     */
    private $authorizationHydrator;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param TemplateInterface $template
     * @param AuthorizationService $authorizationService
     * @param AuthorizationHydrator $authorizationHydrator
     * @param Clock $clock
     */
    public function __construct(
        TemplateInterface $template,
        AuthorizationService $authorizationService,
        AuthorizationHydrator $authorizationHydrator,
        Clock $clock
    ) {
        $this->template = $template;
        $this->authorizationService = $authorizationService;
        $this->authorizationHydrator = $authorizationHydrator;

        $this->clock = $clock;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);
        $currentUser = $this->getAuthorizations($request);

        $authorizations = $this->authorizationService->getUserAuthorizations($user);
        $permissions = $this->authorizationHydrator->hydrateAuthorizations($user, $authorizations);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'user_authorizations' => $authorizations,
            'user_permissions' => $permissions,

            'can_disable' => $this->canDisableUser($authorizations, $currentUser),
            'is_setup_token_expired' => $this->isSetupTokenExpired($user),

            'tokens' => $user->tokens()->toArray(),
        ]);
    }

    /**
     * @param UserAuthorizations $selectedUser
     * @param UserAuthorizations $currentUser
     *
     * @return bool
     */
    private function canDisableUser(UserAuthorizations $selectedUser, UserAuthorizations $currentUser)
    {
        if ($currentUser->isSuper()) {
            return true;
        }

        if ($currentUser->isAdmin() && !$selectedUser->isSuper()) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function isSetupTokenExpired(User $user)
    {
        $identity = $user->identities()->first();

        $expiry = $identity->parameter(Parameters::ID_INTERNAL_SETUP_EXPIRY);
        if (!$expiry) {
            return false;
        }

        $expiry = $this->clock->fromString($expiry);
        if (!$expiry) {
            return false;
        }

        return !$this->clock->inRange($expiry, null, '5 minutes');
    }
}
