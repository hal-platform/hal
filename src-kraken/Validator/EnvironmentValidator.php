<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Crypto\Package\TamperResistantPackage;
use QL\Kraken\Core\Entity\Environment;

class EnvironmentValidator
{
    const ERR_INVALID_NAME = 'Environment name must consist of letters, underscores and/or hyphens.';
    const ERR_INVALID_TOKEN = 'Consul token must be at least two alphanumeric characters without spaces.';
    const ERR_INVALID_CONSUL_SERVICE = 'Invalid Consul service URL.';

    const ERR_INVALID_QKS_SERVICE = 'Invalid QKS service URL.';
    const ERR_INVALID_QKS_KEY = 'Encryption key must be 6 alphanumeric characters.';
    const ERR_INVALID_CLIENT_ID = 'Client ID must be at least two alphanumeric characters without spaces.';
    const ERR_INVALID_CLIENT_SECRET = 'Client secret must be at least two alphanumeric characters without spaces';

    const ERR_DUPLICATE_NAME = 'An environment with this name already exists.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z_-]{2,40}$/';
    const VALIDATE_TOKEN_REGEX = '/^[a-zA-Z0-9\-\=\.\+\/]{0,40}$/';
    const VALIDATE_QKS_KEY_REGEX = '/^[0-9A-Z]{6}$/';

    /**
     * @var callable
     */
    private $random;

    /**
     * @var TamperResistantPackage
     */
    private $encryption;

    /**
     * @var EntityRepository
     */
    private $environmentRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param TamperResistantPackage $encryption
     * @param callable $random
     */
    public function __construct(EntityManagerInterface $em, TamperResistantPackage $encryption, callable $random)
    {
        $this->encryption = $encryption;
        $this->random = $random;
        $this->environmentRepo = $em->getRepository(Environment::CLASS);

        $this->errors = [];
    }

    /**
     * @param string $name
     * @param string $isProd
     * @param string $consulService
     * @param string $consulToken
     * @param string $qksService
     * @param string $qksKey
     * @param string $qksClient
     * @param string $qksSecret
     *
     * @return Environment|null
     */
    public function isValid($name, $isProd, $consulService, $consulToken, $qksService, $qksKey, $qksClient, $qksSecret)
    {
        $this->errors = [];

        if (preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }

        $consulURL = $this->validateConsul($consulService, $consulToken);
        $qksURL = $this->validateQKS($qksService, $qksKey, $qksClient, $qksSecret);

        // dupe check
        if (!$this->errors && $dupe = $this->environmentRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) {
            return null;
        }

        $id = call_user_func($this->random);
        $isProd = ($isProd === 'true');

        if ($consulToken !== '') {
            $consulToken = $this->encryption->encrypt($consulToken);
        }

        if ($qksSecret !== '') {
            $qksSecret = $this->encryption->encrypt($qksSecret);
        }

        $environment = (new Environment)
            ->withId($id)
            ->withName($name)
            ->withIsProduction($isProd)

            ->withConsulServiceURL($consulURL)
            ->withConsulToken($consulToken)

            ->withQKSServiceURL($qksURL)
            ->withQKSEncryptionKey($qksKey)

            ->withQKSClientID($qksClient)
            ->withQKSClientSecret($qksSecret);

        return $environment;
    }

    /**
     * @param string $consulService
     * @param string $consulToken
     * @param string $qksService
     * @param string $qksKey
     * @param string $qksClient
     * @param string $qksSecret
     *
     * @return Environment|null
     */
    public function isEditValid(Environment $environment, $consulService, $consulToken, $qksService, $qksKey, $qksClient, $qksSecret)
    {
        $this->errors = [];

        $consulURL = $this->validateConsul($consulService, $consulToken);
        $qksURL = $this->validateQKS($qksService, $qksKey, $qksClient, $qksSecret);

        if ($this->errors) {
            return null;
        }

        if ($consulToken !== '') {
            $consulToken = $this->encryption->encrypt($consulToken);
        }

        if ($qksSecret !== '') {
            $qksSecret = $this->encryption->encrypt($qksSecret);
        }

        $environment
            ->withConsulServiceURL($consulURL)
            ->withConsulToken($consulToken)

            ->withQKSServiceURL($qksURL)
            ->withQKSEncryptionKey($qksKey)

            ->withQKSClientID($qksClient)
            ->withQKSClientSecret($qksSecret);

        return $environment;
    }

    /**
     * @param string $service
     * @param string $token
     *
     * @return string
     */
    private function validateConsul($service, $token)
    {
        if (preg_match(self::VALIDATE_TOKEN_REGEX, $token) !== 1) {
            $this->errors[] = self::ERR_INVALID_TOKEN;
        }

        $url = ($service) ? $this->validateUrl($service) : '';

        if ($url === null) {
            $this->errors[] = self::ERR_INVALID_CONSUL_SERVICE;
        }

        return $url;
    }

    /**
     * @param string $service
     * @param string $encryptionKey
     * @param string $clientID
     * @param string $clientSecret
     *
     * @return string
     */
    private function validateQKS($service, $encryptionKey, $clientID, $clientSecret)
    {
        if (strlen($encryptionKey) > 0 && preg_match(self::VALIDATE_QKS_KEY_REGEX, $encryptionKey) !== 1) {
            $this->errors[] = self::ERR_INVALID_QKS_KEY;
        }

        if (preg_match(self::VALIDATE_TOKEN_REGEX, $clientID) !== 1) {
            $this->errors[] = self::ERR_INVALID_CLIENT_ID;
        }

        if (preg_match(self::VALIDATE_TOKEN_REGEX, $clientSecret) !== 1) {
            $this->errors[] = self::ERR_INVALID_CLIENT_SECRET;
        }

        $url = ($service) ? $this->validateUrl($service) : '';

        if ($url === null) {
            $this->errors[] = self::ERR_INVALID_QKS_SERVICE;
        }

        return $url;
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    private function validateUrl($url)
    {
        if (strlen($url) > 200) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }
}
