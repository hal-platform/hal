<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserIdentity;
use Hal\Core\Parameters;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Validator\UserIdentityValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RegenerateSetupTokenHandler implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = 'Setup token regnerated successfully.';
    private const MSG_FIRST_TIME_SIGNIN = <<<'HTML'
Please note this user cannot yet sign in. Send them to the following URL to create their password:<br>
<input type="text" style="color:black;" value="%s" readonly>
This link will expire in 8 hours.
HTML;

    private const ERR_CSRF_OR_STATE = 'An error occurred. Please try again.';
    private const ERR_INTERNAL_ONLY = 'Only users using internal authentication can be manually reset.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserIdentityValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param UserIdentityValidator $validator
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, UserIdentityValidator $validator, URI $uri)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        $identity = $user->identities()->first();

        if ($identity->provider()->type() !== IdentityProviderEnum::TYPE_INTERNAL) {
            $this->withFlashError($request, self::ERR_INTERNAL_ONLY);
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
        }

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, self::ERR_CSRF_OR_STATE);
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
        }

        $changed = $this->validator->resetIdentitySetup($identity);
        if (!$changed) {
            $this->withFlashError($request, self::ERR_CSRF_OR_STATE);
            return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
        }

        $this->em->merge($changed);
        $this->em->flush();

        return $this->sendSuccessInstructions($identity, $request, $response);
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
                'setup_token' => $identity->parameter(Parameters::ID_INTERNAL_SETUP_TOKEN)
            ]
        );

        $firstTimeMsg = sprintf(self::MSG_FIRST_TIME_SIGNIN, $setupURL);

        $this->withFlashSuccess($request, self::MSG_SUCCESS, $firstTimeMsg);
        return $this->withRedirectRoute($response, $this->uri, 'user', ['user' => $user->id()]);
    }
}
