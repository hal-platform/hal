<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Identity;
use Hal\Core\Entity\System\UserIdentityProvider;
use QL\MCP\Common\GUID;
use QL\MCP\Common\Time\Clock;
use Psr\Http\Message\ServerRequestInterface;

class IdentityValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const SETUP_EXPIRY = '+8 hours';

    private const REGEX_CHARACTER_CLASS_USERNAME = 'a-zA-Z0-9_\.\-';
    private const REGEX_CHARACTER_WHITESPACE_SPACE = '\f\n\r\t\v ';

    private const ERR_USERNAME_CHARACTERS = 'Username must contain no spaces or special characters.';
    private const ERR_DUPE_USERNAME = 'A user with this username already exists.';

    private const ERR_INVALID_IDP = 'Please select a valid identity provider.';
    private const ERR_CANNOT_RESET = 'Cannot reset user setup token.';

    /**
     * @var EntityRepository
     */
    private $identityRepo;
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
        $this->identityRepo = $em->getRepository(Identity::class);
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);

        $this->clock = $clock;
    }

    /**
     * @param array $data
     *
     * @return Identity|null
     */
    public function isValid(array $data): ?Identity
    {
        $providerID = $data['id_provider'] ?? '';
        $internalUsername = $data['internal_username'] ?? '';

        $this->resetErrors();

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

        if ($dupe = $this->identityRepo->findOneBy(['provider' => $idp, 'providerUniqueID' => $internalUsername])) {
            $this->addError(self::ERR_DUPE_USERNAME);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $identity = (new Identity)
            ->withProviderUniqueID($internalUsername)
            ->withProvider($idp);

        return $this->resetIdentitySetup($identity);
    }

    /**
     * @param Identity $identity
     * @param array $data
     *
     * @return Identity|null
     */
    public function isEditValid(Identity $identity, array $data): ?Identity
    {
        return null;
    }

    /**
     * @param Identity $identity
     *
     * @return Identity|null
     */
    public function resetIdentitySetup(Identity $identity): ?Identity
    {
        $this->resetErrors();

        if ($identity->parameter('internal.password')) {
            $this->addError(self::ERR_CANNOT_RESET, 'username');
            return null;
        }

        $setupToken = GUID::create()->format(GUID::STANDARD | GUID::HYPHENATED);
        $tokenExpiry = $this->clock
            ->read()
            ->modify(IdentityValidator::SETUP_EXPIRY)
            ->format('Y-m-d\TH:i:s\Z', 'UTC');

        $identity
            ->withParameter('internal.setup_token', $setupToken)
            ->withParameter('internal.setup_token_expiry', $tokenExpiry);

        return $identity;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Identity|null $identity
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?Identity $identity): array
    {
        $data = $request->getParsedBody();

        $form = [];

        return $form;
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
