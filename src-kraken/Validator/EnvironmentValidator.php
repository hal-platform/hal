<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Environment;
use MCP\DataType\HttpUrl;

class EnvironmentValidator
{
    const ERR_INVALID_NAME = 'Invalid Name. Environment names must be at least two alphanumeric characters.';
    const ERR_INVALID_TOKEN = 'Invalid Token. Consul token must be at least two alphanumeric characters.';
    const ERR_INVALID_SERVER = 'Invalid Consul service URL.';

    const ERR_DUPLICATE_NAME = 'An environment with this name already exists.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9]{2,40}$/';
    const VALIDATE_TOKEN_REGEX = '/^[a-zA-Z0-9\-\=\.\+\/]{0,40}$/';

    /**
     * @type callable
     */
    private $random;

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
     * @param callable $random
     */
    public function __construct(EntityManagerInterface $em, callable $random)
    {
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

        $consulURL = '';
        if ($consulService) {
            $consulURL = $this->validateConsul($consulService, $consulToken);
            if ($consulURL) $consulURL = $consulURL->asString();
        }

        // dupe check
        if (!$this->errors && $dupe = $this->environmentRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) {
            return null;
        }

        $id = call_user_func($this->random);
        $isProd = ($isProd === 'true');

        $environment = (new Environment)
            ->withId($id)
            ->withName($name)
            ->withIsProduction($isProd);

        if ($consulService) {
            $environment
                ->withConsulServiceURL($consulURL->asString())
                ->withConsulToken($consulToken);
        }

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
    public function isValid(Environment $environment, $consulService, $consulToken, $qksService, $qksClient, $qksSecret)
    {
        $this->errors = [];

        $consulURL = '';
        if ($consulService) {
            $consulURL = $this->validateConsul($consulService, $consulToken);
            if ($consulURL) $consulURL = $consulURL->asString();
        }

        $qksURL = '';
        if ($qksService) {
            $qksURL = $this->validateQKS($qksService, $qksClient, $qksSecret);
            if ($qksURL) $qksURL = $qksURL->asString();
        }

        if ($this->errors) {
            return null;
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
     * @return HttpUrl|null
     */
    private function validateConsul($service, $token)
    {
        if (preg_match(self::VALIDATE_TOKEN_REGEX, $token) !== 1) {
            $this->errors[] = self::ERR_INVALID_TOKEN;
        }

        $url = ($service) ? HttpUrl::create($service) : null;

        if ($url === null) {
            $this->errors[] = self::ERR_INVALID_SERVER;
        }

        return $url;
    }

    /**
     * @param string $server
     * @param string $token
     *
     * @return HttpUrl|null
     */
    private function validateQKS($service, $clientID, $clientSecret)
    {
        if (preg_match(self::VALIDATE_TOKEN_REGEX, $token) !== 1) {
            $this->errors[] = self::ERR_INVALID_TOKEN;
        }

        $url = ($server) ? HttpUrl::create($server) : null;

        if ($url === null) {
            $this->errors[] = self::ERR_INVALID_SERVER;
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
