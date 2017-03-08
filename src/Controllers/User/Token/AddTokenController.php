<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\Token;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
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
     * @param callable $random
     */
    public function __construct(EntityManagerInterface $em, URI $uri, callable $random)
    {
        $this->em = $em;
        $this->uri = $uri;
        $this->random = $random;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        $label = $request->getParsedBody()['label'] ?? '';

        if (!$label) {
            $this->withFlash($request, Flash::ERROR, self::ERR_NAME_REQUIRED);
            return $this->withRedirectRoute($response, $this->uri, 'settings');
        }

        $token = $this->generateToken($user, $label);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $token->label()));
        return $this->withRedirectRoute($response, $this->uri, 'settings');
    }

    /**
     * @param User $user
     * @param string $label
     *
     * @return Token
     */
    private function generateToken(User $user, $label): Token
    {
        $hash = sha1(call_user_func($this->random));

        $id = call_user_func($this->random);
        $token = (new Token($id))
            ->withLabel($label)
            ->withValue($hash)
            ->withUser($user);

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }
}
