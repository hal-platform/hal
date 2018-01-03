<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security\UserAuthentication;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use function password_verify;

class InternalAuth
{
    use ValidatorErrorTrait;

    // todo move to UserIdentityProvider?
    const PARAM_PW = 'internal.password';

    const ERR_USER_NOT_FOUND = 'Invalid sign-in information. Please try again.';
    const ERR_IDP_MISCONFIGURED = 'Internal Auth Identity Provider is misconfigured.';

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->userRepo = $em->getRepository(User::class);
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string $username
     * @param string $password
     *
     * @return User|null
     */
    public function authenticate(UserIdentityProvider $idp, string $username, string $password): ?User
    {
        if (strlen($username) === 0 || strlen($password) === 0) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $user = $this->getUserData($idp, $username);
        if (!$user) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $hashed = $user->parameter(self::PARAM_PW);
        if (strlen($hashed) === 0) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $isGood = password_verify($password, $hashed);
        if (!$isGood) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        return $user;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string $username
     *
     * @return User|null
     */
    private function getUserData(UserIdentityProvider $idp, $username)
    {
        if ($idp->type() !== IdentityProviderEnum::TYPE_INTERNAL) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        $user = $this->userRepo->findOneBy([
            'provider' => $idp,
            'providerUniqueID' => $username
        ]);

        if (!$user instanceof User) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        return $user;
    }
}
