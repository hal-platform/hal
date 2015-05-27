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
    const ERR_INVALID_SERVER = 'Invalid Server.';

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
     * @param string $server
     * @param string $token
     * @param string $isProd
     *
     * @return Environment|null
     */
    public function isValid($name, $server, $token, $isProd)
    {
        $this->errors = [];

        if (preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }

        $url = $this->validateConsul($server, $token);

        // dupe check
        if (!$this->errors && $dupe = $this->environmentRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) {
            return null;
        }

        $id = call_user_func($this->random);
        $isProd = ($isProd === 'true');

        return (new Environment)
            ->withId($id)
            ->withName($name)
            ->withIsProduction($isProd)
            ->withConsulServer($url->asString())
            ->withConsulToken($token);
    }

    /**
     * @param Environment $environment
     * @param string $server
     * @param string $token
     *
     * @return Environment|null
     */
    public function isEditValid(Environment $environment, $server, $token)
    {
        $this->errors = [];

        $url = $this->validateConsul($server, $token);

        if ($this->errors) {
            return null;
        }

        $environment
            ->withConsulServer($url->asString())
            ->withConsulToken($token);

        return $environment;
    }

    /**
     * @param string $server
     * @param string $token
     *
     * @return HttpUrl|null
     */
    private function validateConsul($server, $token)
    {
        if (preg_match(self::VALIDATE_TOKEN_REGEX, $token) !== 1) {
            $this->errors[] = self::ERR_INVALID_TOKEN;
        }

        $url = HttpUrl::create($server);

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
