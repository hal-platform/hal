<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use QL\MCP\Common\GUID;
use QL\MCP\Common\Time\Clock;

class UserValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const SETUP_EXPIRY = '+8 hours';

    private const REGEX_CHARACTER_CLASS_USERNAME = 'a-zA-Z0-9_\.\-';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';
    private const REGEX_CHARACTER_WHITESPACE_SPACE = '\f\n\r\t\v ';

    private const ERR_NAME_CHARACTERS = 'Name must not contain tabs or newlines.';
    private const ERR_USERNAME_CHARACTERS = 'Username must contain no spaces or special characters.';
    private const ERR_DUPE_USERNAME = 'A user with this username already exists.';

    private const ERR_INVALID_IDP = 'Please select a valid identity provider.';
    private const ERR_CANNOT_RESET = 'Cannot reset user setup token.';

    /**
     * @var EntityRepository
     */
    private $userRepo;
    private $idpRepo;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param EntityManagerInterface $em
     * @param Clock $clock
     */
    public function __construct(EntityManagerInterface $em, Clock $clock)
    {
        $this->userRepo = $em->getRepository(User::class);
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);

        $this->clock = $clock;
    }

    /**
     * @param array $data
     *
     * @return User|null
     */
    public function isValid(array $data): ?User
    {
        $name = $data['name'] ?? '';
        $providerID = $data['id_provider'] ?? '';
        $internalUsername = $data['internal_username'] ?? '';

        $this->resetErrors();

        $this->validateName($name);

        if ($this->hasErrors()) {
            return null;
        }

        $idp = null;
        if (!$providerID || !$idp = $this->idpRepo->find($providerID)) {
            $this->addError(self::ERR_INVALID_IDP);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Only internal allowed for now.
        if ($idp->type() !== 'internal') {
            $this->addError(self::ERR_INVALID_IDP);
        }

        $this->validateUsername($internalUsername);

        if ($dupe = $this->userRepo->findOneBy(['provider' => $idp, 'providerUniqueID' => $internalUsername])) {
            $this->addError(self::ERR_DUPE_USERNAME);
        }

        if ($this->hasErrors()) {
            return null;
        }


        $setupToken = GUID::create()->format(GUID::STANDARD | GUID::HYPHENATED);
        $tokenExpiry = $this->clock
            ->read()
            ->modify(self::SETUP_EXPIRY)
            ->format('Y-m-d\TH:i:s\Z', 'UTC');

        $user = (new User)
            ->withName($name)
            ->withProviderUniqueID($internalUsername)
            ->withProvider($idp);

        return $this->resetUserSetup($user);
    }

    /**
     * @param User $user
     * @param array $data
     *
     * @return User|null
     */
    public function isEditValid(User $user, array $data): ?User
    {
        // @todo
    }

    /**
     * @param User $user
     *
     * @return User|null
     */
    public function resetUserSetup(User $user): ?User
    {
        $this->resetErrors();

        if ($user->parameter('internal.password')) {
            $this->addError(self::ERR_CANNOT_RESET, 'name');
            return null;
        }

        $setupToken = GUID::create()->format(GUID::STANDARD | GUID::HYPHENATED);
        $tokenExpiry = $this->clock
            ->read()
            ->modify(UserValidator::SETUP_EXPIRY)
            ->format('Y-m-d\TH:i:s\Z', 'UTC');

        $user
            ->withParameter('internal.setup_token', $setupToken)
            ->withParameter('internal.setup_token_expiry', $tokenExpiry);

        return $user;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateLength($name, 3, 100)) {
            $this->addLengthError('Name', 3, 100, 'name');
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }
    }

    /**
     * @param string $username
     *
     * @return void
     */
    private function validateUsername($username)
    {
        if (!$this->validateIsRequired($username) || !$this->validateSanityCheck($username)) {
            $this->addRequiredError('Username', 'internal_username');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateLength($username, 3, 100)) {
            $this->addLengthError('Username', 3, 100, 'internal_username');
        }

        if (!$this->validateCharacterBlacklist($username, self::REGEX_CHARACTER_WHITESPACE_SPACE)) {
            $this->addError(self::ERR_USERNAME_CHARACTERS, 'internal_username');
        }
    }
}
