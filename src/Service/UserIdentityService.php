<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User\UserIdentity;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Provider\GithubResourceOwner;

class UserIdentityService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userIdentityRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->userIdentityRepo = $em->getRepository(UserIdentity::class);
    }

    /**
     * @param UserIdentityProvider $idp
     * @param ResourceOwnerInterface $owner
     *
     * @return User|null
     */
    public function getOrCreateUser(UserIdentityProvider $idp, ResourceOwnerInterface $owner)
    {
        $userID = $owner->getId();

        $identity = $this->userIdentityRepo->findOneBy([
            'provider' => $idp,
            'providerUniqueID' => $userID
        ]);

        if ($identity instanceof UserIdentity) {
            return $identity->user();
        }

        $data = $this->getUserData($owner);

        $user = $this->createUser($data);
        $identity = $this->createIdentity($data, $user, $idp);

        $user->identities()->add($identity);

        $this->em->persist($user);
        $this->em->persist($identity);
        $this->em->flush();

        return $user;
    }

    /**
     * @param ResourceOwnerInterface $owner
     *
     * @return array
     */
    private function getUserData(ResourceOwnerInterface $owner)
    {
        $data = [];

        if ($owner instanceof GithubResourceOwner) {
            $data = [
                'unique_id' => $owner->getId(),
                'username' => $owner->getNickname()
            ];
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return User|null
     */
    private function createUser($data)
    {
        $user = (new User())
            ->withName($data['username']);

        return $user;
    }

    /**
     * @param array $data
     *
     * @return User|null
     */
    private function createIdentity($data, $user, $idp)
    {
        $identity = (new UserIdentity)
            ->withProviderUniqueID($data['unique_id'])
            ->withProvider($idp)
            ->withUser($user);

        return $identity;
    }
}
