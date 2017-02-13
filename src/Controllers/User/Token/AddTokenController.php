<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\Token;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use Slim\Http\Request;

class AddTokenController implements ControllerInterface
{
    const SUCCESS = 'Token "%s" created successfully.';
    const ERR_DENIED = 'You do not have permission to perform this action.';
    const ERR_LABEL_REQUIRED = 'Token label is required to create a token.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     *
     * @param User $user
     * @param Flasher $flasher
     *
     * @param Request $request
     * @param NotFound $notFound
     * @param callable $random
     *
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        PermissionService $permissions,

        User $currentUser,
        Flasher $flasher,

        Request $request,
        NotFound $notFound,
        callable $random,

        array $parameters
    ) {
        $this->em = $em;
        $this->userRepo = $em->getRepository(User::CLASS);

        $this->permissions = $permissions;

        $this->currentUser = $currentUser;
        $this->flasher = $flasher;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->random = $random;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $label = $this->request->post('label');
        $id = $this->parameters['id'];

        if (!$user = $this->userRepo->find($id)) {
            return call_user_func($this->notFound);
        }

        if (!$this->isUserAllowed($user)) {
            return $this->flasher
                ->withFlash(self::ERR_DENIED, 'error')
                ->load('settings');
        }

        if (!$label) {
            return $this->flasher
                ->withFlash(self::ERR_LABEL_REQUIRED, 'error')
                ->load('settings');
        }

        $token = $this->generateToken($user, $label);
        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $token->label()), 'success')
            ->load('settings');
    }

    /**
     * @param User $user
     * @param string $label
     *
     * @return Token
     */
    private function generateToken(User $user, $label)
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

    /**
     * Does the user have the correct permissions to access this page?
     *
     * @param User $user
     * @return boolean
     */
    private function isUserAllowed(User $user)
    {
        $perm = $this->permissions->getUserPermissions($this->currentUser);

        if ($perm->isSuper()) {
            return true;
        }

        return ($this->currentUser->id() == $user->id());
    }
}
