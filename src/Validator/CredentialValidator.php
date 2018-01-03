<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Crypto\Encryption;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Credential\PrivateKeyCredential;
use Hal\Core\Entity\Credential\AWSRoleCredential;
use Hal\Core\Entity\Credential\AWSStaticCredential;
use Hal\Core\Type\CredentialEnum;

class CredentialValidator
{
    use ValidatorErrorTrait;
    use NewValidatorTrait;

    private const REGEX_CHARACTER_CLASS_USERNAME = '0-9a-zA-Z_.-';
    private const REGEX_CHARACTER_CLASS_PATH = '0-9a-zA-Z_.-\\\/\ \:';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';

    private const REGEX_CREDENTIAL_NAME = '/^[a-zA-Z0-9:().\-\/\\\ ]{3,100}$/';
    private const REGEX_ROLE = '/^[a-zA-Z0-9_\+\=,\.@\-\/]{10,100}$/';

    private const ERR_NAME_CHARACTERS = 'Name must not contain tabs or newlines';
    private const ERR_INVALID_TYPE = 'Credential Type is required';

    private const ERR_INVALID_NAME = 'Credential name must not contain special characters';
    private const ERR_INVALID_KEY = 'AWS Access Key must not contain special characters';
    private const ERR_INVALID_SECRET = 'AWS Secret Key must not contain special characters';
    private const ERR_INVALID_ACCOUNT = 'AWS Account ID must not contain special characters';
    private const ERR_INVALID_ROLE = 'AWS Role must not contain special characters';

    private const ERR_INVALID_USERNAME = 'Username must contain only alphanumeric characters with periods (.), underscore (_), and dashes (-)';
    private const ERR_INVALID_PATH = 'Private Key Path must be a path to a private key file';

    /**
     * @var EntityRepository
     */
    private $credentialRepo;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @param EntityManagerInterface $em
     * @param Encryption $encryption
     */
    public function __construct(EntityManagerInterface $em, Encryption $encryption)
    {
        $this->credentialRepo = $em->getRepository(Credential::class);
        $this->encryption = $encryption;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @param string $awsKey
     * @param string $awsSecret
     *
     * @param string $awsAccount
     * @param string $awsRole
     *
     * @param string $privateKeyUsername
     * @param string $privateKeyPath
     * @param string $privateKeyFile
     *
     * @param bool $isInternal
     *
     * @return Credential|null
     */
    public function isValid(
        $type,
        $name,
        $awsKey,
        $awsSecret,
        $awsAccount,
        $awsRole,
        $privateKeyUsername,
        $privateKeyPath,
        $privateKeyFile,
        $isInternal
    ): ?Credential {
        $this->resetErrors();

        $this->validate($type, $name);

        if ($this->hasErrors()) {
            return null;
        }

        $details = null;
        if ($type === CredentialEnum::TYPE_AWS_STATIC) {
            $this->validateStatic($awsKey, $awsSecret);

            $encrypted = $this->encryption->encrypt($awsSecret);
            $details = new AWSStaticCredential($awsKey, $encrypted);

        } elseif ($type === CredentialEnum::TYPE_AWS_ROLE) {
            $this->validateRole($awsAccount, $awsRole);
            $details = new AWSRoleCredential($awsAccount, $awsRole);

        } elseif ($type === CredentialEnum::TYPE_PRIVATEKEY) {
            $this->validatePrivateKey($privateKeyUsername, $privateKeyPath, $privateKeyFile);

            $encrypted = $privateKeyFile ? $this->encryption->encrypt($privateKeyFile) : '';
            $keyPath = $privateKeyFile ? '' : $privateKeyPath;
            $details = new PrivateKeyCredential($privateKeyUsername, $keyPath, $encrypted);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $credential = (new Credential)
            ->withName($name)
            ->withIsInternal($isInternal)
            ->withDetails($details);

        return $credential;
    }

    /**
     * @param Credential $credential
     * @param string $type
     * @param string $name
     *
     * @param string $awsKey
     * @param string $awsSecret
     *
     * @param string $awsAccount
     * @param string $awsRole
     *
     * @param string $privateKeyUsername
     * @param string $privateKeyPath
     * @param string $privateKeyFile
     *
     * @param bool $isInternal
     *
     * @return Credential|null
     */
    public function isEditValid(
        Credential $credential,
        $type,
        $name,
        $awsKey,
        $awsSecret,
        $awsAccount,
        $awsRole,
        $privateKeyUsername,
        $privateKeyPath,
        $privateKeyFile,
        $isInternal
    ): ?Credential {
        $this->resetErrors();

        $this->validate($type, $name);

        if ($this->hasErrors()) {
            return null;
        }

        $details = null;
        if ($type === CredentialEnum::TYPE_AWS_STATIC) {
            $this->validateStatic($awsKey, $awsSecret);

            $encrypted = $this->encryption->encrypt($awsSecret);
            $details = new AWSStaticCredential($awsKey, $encrypted);

        } elseif ($type === CredentialEnum::TYPE_AWS_ROLE) {
            $this->validateRole($awsAccount, $awsRole);
            $details = new AWSRoleCredential($awsAccount, $awsRole);

        } elseif ($type === CredentialEnum::TYPE_PRIVATEKEY) {
            $this->validatePrivateKey($privateKeyUsername, $privateKeyPath, $privateKeyFile);

            $encrypted = $privateKeyFile ? $this->encryption->encrypt($privateKeyFile) : '';
            $keyPath = $privateKeyFile ? '' : $privateKeyPath;
            $details = new PrivateKeyCredential($privateKeyUsername, $keyPath, $encrypted);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $credential = $credential
            ->withName($name)
            ->withIsInternal($isInternal)
            ->withDetails($details);

        return $credential;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return bool
     */
    private function validate($type, $name): bool
    {
        if (!$this->validateIsRequired($type) || !$this->validateSanityCheck($type)) {
            $this->addRequiredError('Credential Type', 'type');
        }

        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if ($this->validateCharacterWhitelist($name, self::REGEX_CREDENTIAL_NAME)) {
            $this->addError(self::ERR_INVALID_NAME, 'name');
        }

        if (!CredentialEnum::isValid($type)) {
            $this->addError(self::ERR_INVALID_TYPE, 'type');
        }

        if (!$this->validateLength($name, 3, 100)) {
            $this->addLengthError('Name', 3, 100, 'name');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }

        return !$this->hasErrors();
    }

    /**
     * @param string $key
     * @param string $secret
     *
     * @return bool
     */
    private function validateStatic($key, $secret): bool
    {
        if (!$this->validateIsRequired($key) || !$this->validateSanityCheck($key)) {
            $this->addRequiredError('AWS Access Key', 'aws_key');
        }

        if (!$this->validateIsRequired($secret) || !$this->validateSanityCheck($secret)) {
            $this->addRequiredError('AWS Secret', 'aws_secret');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateLength($key, 3, 100)) {
            $this->addLengthError('AWS Access Key', 3, 100, 'aws_key');
        }

        if (!$this->validateLength($secret, 3, 100000)) {
            $this->addLengthError('AWS Secret', 3, 100, 'aws_secret');
        }

        if (!$this->validateCharacterBlacklist($key, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_INVALID_KEY, 'aws_key');
        }

        if (!$this->validateCharacterBlacklist($secret, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_INVALID_SECRET, 'aws_secret');
        }

        return !$this->hasErrors();
    }

    /**
     * @param string $accountID
     * @param string $roleName
     *
     * @return bool
     */
    private function validateRole($accountID, $roleName): bool
    {
        if (!$this->validateIsRequired($accountID) || !$this->validateSanityCheck($accountID)) {
            $this->addRequiredError('AWS Account ID', 'aws_account');
        }

        if (!$this->validateIsRequired($roleName) || !$this->validateSanityCheck($roleName)) {
            $this->addRequiredError('AWS Role', 'aws_role');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateLength($accountID, 3, 25)) {
            $this->addLengthError('AWS Account ID', 3, 25, 'aws_account');
        }

        if (!$this->validateCharacterWhitelist($roleName, self::REGEX_ROLE)) {
            $this->addError(self::ERR_INVALID_ROLE, 'aws_role');
        }

        if (!$this->validateCharacterBlacklist($accountID, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_INVALID_ACCOUNT, 'aws_account');
        }

        if (!$this->validateCharacterBlacklist($roleName, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_INVALID_ROLE, 'aws_role');
        }

        return !$this->hasErrors();
    }

    /**
     * @param string $username
     * @param string $keyPath
     * @param string $keyFile
     *
     * @return bool
     */
    private function validatePrivateKey($username, $keyPath, $keyFile): bool
    {
        if (!$this->validateIsRequired($username) || !$this->validateSanityCheck($username)) {
            $this->addRequiredError('Username', 'privatekey_username');
        }

        if (!$keyFile) {
            if (!$this->validateIsRequired($keyPath) || !$this->validateSanityCheck($keyPath)) {
                $this->addRequiredError('Key Path', 'privatekey_path');
            }
        }

        if (!$keyPath) {
            if (!$this->validateIsRequired($keyFile) || !$this->validateSanityCheck($keyFile)) {
                $this->addRequiredError('Key File', 'privatekey_file');
            }
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateLength($username, 3, 100)) {
            $this->addLengthError('Username', 3, 100, 'privatekey_username');
        }

        if (!$this->validateCharacterWhitelist($username, self::REGEX_CHARACTER_CLASS_USERNAME)) {
            $this->addError(self::ERR_INVALID_USERNAME, 'privatekey_username');
        }

        if ($keyPath) {
            if (!$this->validateLength($keyPath, 3, 200)) {
                $this->addLengthError('Key Path', 3, 200, 'privatekey_path');
            }

            if (!$this->validateCharacterWhitelist($keyPath, self::REGEX_CHARACTER_CLASS_PATH)) {
                $this->addError(self::ERR_INVALID_PATH, 'privatekey_path');
            }
        }

        if ($keyFile) {
            if (!$this->validateLength($keyFile, 3, 100000)) {
                $this->addLengthError('Key File', 3, 100000, 'privatekey_file');
            }
        }

        return !$this->hasErrors();
    }
}
