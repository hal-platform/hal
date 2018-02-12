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
use Hal\Core\Entity\User\UserIdentity;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use function password_verify;

class InternalAuth
{
    use ValidatorErrorTrait;

    // todo move to UserIdentityProvider?
    const PARAM_PW = 'internal.password';

    const ERR_IDENTITY_NOT_FOUND = 'Invalid sign-in information. Please try again.';
    const ERR_IDP_MISCONFIGURED = 'Internal Auth Identity Provider is misconfigured.';

    /**
     * @var EntityRepository
     */
    private $identityRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->identityRepo = $em->getRepository(UserIdentity::class);
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
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        }

        $identity = $this->getIdentityData($idp, $username);
        if (!$identity) {
            return null;
        }

        $hashed = $identity->parameter(self::PARAM_PW);
        if (strlen($hashed) === 0) {
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        }

        $isGood = password_verify($password, $hashed);
        if (!$isGood) {
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        }

        return $identity->user();
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string $username
     *
     * @return UserIdentity|null
     */
    private function getIdentityData(UserIdentityProvider $idp, $username)
    {
        if ($idp->type() !== IdentityProviderEnum::TYPE_INTERNAL) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        $identity = $this->identityRepo->findOneBy([
            'provider' => $idp,
            'providerUniqueID' => $username
        ]);

        if (!$identity instanceof UserIdentity) {
            $this->addError(self::ERR_IDENTITY_NOT_FOUND);
            return null;
        }

        return $identity;
    }
}
