<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Crypto\Package\TamperResistantPackage;
use MCP\DataType\HttpUrl;
use QL\Kraken\Core\Entity\Environment;

class EnvironmentValidator
{
    const ERR_INVALID_NAME = 'Invalid Name. Environment names must be at least two alphanumeric characters without spaces.';
    const ERR_INVALID_TOKEN = 'Invalid Token. Consul token must be at least two alphanumeric characters without spaces.';
    const ERR_INVALID_CONSUL_SERVICE = 'Invalid Consul service URL.';

    const ERR_INVALID_QKS_SERVICE = 'Invalid QKS service URL.';
    const ERR_INVALID_CLIENT_ID = 'Invalid client ID. Client ID must be at least two alphanumeric characters without spaces.';
    const ERR_INVALID_CLIENT_SECRET = 'Invalid client secret. Client secret must be at least two alphanumeric characters without spaces';

    const ERR_DUPLICATE_NAME = 'An environment with this name already exists.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9]{2,40}$/';
    const VALIDATE_TOKEN_REGEX = '/^[a-zA-Z0-9\-\=\.\+\/]{0,40}$/';

    /**
     * @type callable
     */
    private $random;

    /**
     * @type TamperResistantPackage
     */
    private $encryption;

    /**
     * @type EntityRepository
     */
    private $environmentRepo;

    /**
     * @type array
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
     * @param string $qksClient
     * @param string $qksSecret
     *
     * @return Environment|null
     */
    public function isValid($name, $isProd, $consulService, $consulToken, $qksService, $qksClient, $qksSecret)
    {
        $this->errors = [];

        if (preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }

        $consulURL = ($consulService) ? $this->validateConsul($consulService, $consulToken) : '';
        $qksURL = ($qksService) ? $this->validateQKS($qksService, $qksClient, $qksSecret) : '';

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
            ->withQKSClientID($qksClient)
            ->withQKSClientSecret($qksSecret);

        return $environment;
    }

    /**
     * @param string $consulService
     * @param string $consulToken
     * @param string $qksService
     * @param string $qksClient
     * @param string $qksSecret
     *
     * @return Environment|null
     */
    public function isEditValid(Environment $environment, $consulService, $consulToken, $qksService, $qksClient, $qksSecret)
    {
        $this->errors = [];

        $consulURL = ($consulService) ? $this->validateConsul($consulService, $consulToken) : '';
        $qksURL = ($qksService) ? $this->validateQKS($qksService, $qksClient, $qksSecret) : '';

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

        $url = ($service) ? HttpUrl::create($service) : '';

        if ($url) {
            $url = $url->asString();
        } else {
            $this->errors[] = self::ERR_INVALID_CONSUL_SERVICE;
        }

        return $url;
    }

    /**
     * @param string $service
     * @param string $clientID
     * @param string $clientSecret
     *
     * @return string
     */
    private function validateQKS($service, $clientID, $clientSecret)
    {
        if (preg_match(self::VALIDATE_TOKEN_REGEX, $clientID) !== 1) {
            $this->errors[] = self::ERR_INVALID_CLIENT_ID;
        }

        if (preg_match(self::VALIDATE_TOKEN_REGEX, $clientSecret) !== 1) {
            $this->errors[] = self::ERR_INVALID_CLIENT_SECRET;
        }

        $url = ($service) ? HttpUrl::create($service) : '';

        if ($url) {
            $url = $url->asString();
        } else {
            $this->errors[] = self::ERR_INVALID_QKS_SERVICE;
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
