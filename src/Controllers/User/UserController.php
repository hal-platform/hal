<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Hal\Core\Entity\User;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class UserController implements ControllerInterface
{
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
     * @param TemplateInterface $template
     * @param AuthorizationService $authorizationService
     */
    public function __construct(TemplateInterface $template, AuthorizationService $authorizationService)
    {
        $this->template = $template;
        $this->authorizationService = $authorizationService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);
        $authorizations = $this->authorizationService->getUserAuthorizations($user);

        // $appPerm = $this->permissions->getApplications($userPerm);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'user_authorizations' => $authorizations,
            // 'lead_applications' => $appPerm['lead'],
            // 'prod_applications' => $appPerm['prod'],
            // 'non_prod_applications' => $appPerm['non_prod']
        ]);
    }
}
