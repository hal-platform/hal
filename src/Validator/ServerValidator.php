<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use Aws\Common\Enum\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;

class ServerValidator
{
    const ERR_MISSING_TYPE = 'Please select a type.';
    const ERR_MISSING_ENV = 'Please select an environment.';

    const ERR_HOST_DUPLICATE = 'A server with this hostname already exists.';
    const ERR_EB_DUPLICATE = 'An EB server for this environment already exists.';
    const ERR_EC2_DUPLICATE = 'An EC2 server for this environment already exists.';

    const ERR_HOST = 'Invalid hostname.';
    const ERR_MISSING_HOST = 'Hostname is required for rsync servers.';
    const ERR_LONG_HOST = 'Hostname must be less than or equal to 60 characters.';

    const ERR_INVALID_REGION = 'Invalid AWS region specified.';

    /**
     * @type EntityRepository
     */
    private $envRepo;
    private $serverRepo;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->errors = [];
    }

    /**
     * @param string $serverType
     * @param string $environmentID
     * @param string $hostname
     * @param string $region
     *
     * @return Server|null
     */
    public function isValid($serverType, $environmentID, $hostname, $region)
    {
        $this->errors = [];

        if (!in_array($serverType, ServerEnum::values())) {
            $this->errors[] = self::ERR_MISSING_TYPE;
        }

        if (!$environmentID || !$environment = $this->envRepo->find($environmentID)) {
            $this->errors[] = self::ERR_MISSING_ENV;
        }

        if ($this->errors) return;

        // validate hostname if rsync server
        if ($serverType === ServerEnum::TYPE_RSYNC) {

            $hostname = trim(strtolower($hostname));
            $hostname = $this->validateHostname($hostname);

            if ($this->errors) return;

            if ($server = $this->serverRepo->findOneBy(['name' => $hostname])) {
                $this->errors[] = self::ERR_HOST_DUPLICATE;
            }

        // validate duplicate EB for environment
        // Only 1 EB "server" per environment
        } elseif ($serverType === ServerEnum::TYPE_EB) {
            $hostname = '';

            if ($server = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EB, 'environment' => $environment])) {
                $this->errors[] = self::ERR_EB_DUPLICATE;
            }

        // validate duplicate EC2 for environment
        // Only 1 EC2 "server" per environment
        } elseif ($serverType === ServerEnum::TYPE_EC2) {
            $hostname = '';

            if ($server = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EC2, 'environment' => $environment])) {
                $this->errors[] = self::ERR_EC2_DUPLICATE;
            }
        }

        return (new Server)
            ->withType($serverType)
            ->withEnvironment($environment)
            ->withName($hostname);
    }

    /**
     * @param Server $server
     * @param string $serverType
     * @param string $environmentID
     * @param string $hostname
     * @param string $region
     *
     * @return Server|null
     */
    public function isEditValid(Server $server, $serverType, $environmentID, $hostname, $region)
    {
        $this->errors = [];

        if (!in_array($serverType, ServerEnum::values())) {
            $this->errors[] = self::ERR_MISSING_TYPE;
        }

        if (!$environmentID || !$environment = $this->envRepo->find($environmentID)) {
            $this->errors[] = self::ERR_MISSING_ENV;
        }

        if ($this->errors) return;

        $hasChanged = ($environmentID != $server->environment()->id() || $serverType != $server->type());

        // validate hostname if rsync server
        // RSYNC-hostname (name) pair is unique
        if ($serverType === ServerEnum::TYPE_RSYNC) {

            $name = trim(strtolower($hostname));

            $hasChanged = ($name != $server->name());
            if (!$hasChanged) {
                GOTO SKIP_DUPE_CHECK;
            }

            $name = $this->validateHostname($hostname);

            if ($this->errors) return;

            if ($dupe = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_RSYNC, 'name' => $hostname])) {
                $this->errors[] = self::ERR_HOST_DUPLICATE;
            }

        // validate duplicate EB for environment
        // EB-region (name) pair is unique
        } elseif ($serverType === ServerEnum::TYPE_EB) {

            $name = trim($region);

            $hasChanged = ($name != $server->name());
            if (!$hasChanged) {
                GOTO SKIP_DUPE_CHECK;
            }

            $name = $this->validateRegion($name);

            if ($dupe = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EB, 'name' => $environment])) {
                $this->errors[] = self::ERR_EB_DUPLICATE;
            }

        // validate duplicate EC2 for environment
        // EC2-region (name) pair is unique
        } elseif ($serverType === ServerEnum::TYPE_EC2) {

            $name = trim($region);

            $hasChanged = ($name != $server->name());
            if (!$hasChanged) {
                GOTO SKIP_DUPE_CHECK;
            }

            $name = $this->validateRegion($name);

            if ($dupe = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EC2, 'name' => $environment])) {
                $this->errors[] = self::ERR_EC2_DUPLICATE;
            }

        // validate duplicate S3 for environment
        // S3-region (name) pair is unique
        } elseif ($serverType === ServerEnum::TYPE_S3) {

            $name = trim($region);

            $hasChanged = ($name != $server->name());
            if (!$hasChanged) {
                GOTO SKIP_DUPE_CHECK;
            }

            $name = $this->validateRegion($name);

            if ($dupe = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EC2, 'name' => $environment])) {
                $this->errors[] = self::ERR_EC2_DUPLICATE;
            }
        }

        SKIP_DUPE_CHECK:

        if ($this->errors) return null;

        return $server
            ->withType($serverType)
            ->withEnvironment($environment)
            ->withName($name);
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Validates a hostname through MCP\DataType\HttpUrl
     *
     * @param string $hostname
     *
     * @return string|null
     */
    private function validateHostname($hostname)
    {
        if (strlen($hostname) === 0) {
            $this->errors[] = self::ERR_MISSING_HOST;
        }

        if (strlen($hostname) > 60) {
            $this->errors[] = self::ERR_LONG_HOST;
        }

        if ($this->errors) return;

        $url = HttpUrl::create('//' . $hostname);

        if ($url === null) {
            $this->errors[] = self::ERR_HOST;
            return;
        }

        if (preg_match('/\:([0-9]{1,5})/', $hostname, $match) === 1) {
            $denom = sprintf('%s:%s', $url->host(), $url->port());
        } else {
            $denom = $url->host();
        }

        if ($denom !== $hostname) {
            $this->errors[] = self::ERR_HOST;
            return;
        }

        return $denom;
    }

    /**
     * @param string $region
     *
     * @return string|null
     */
    private function validateRegion($region)
    {
        if (!in_array($region, Region::values(), true)) {
            $this->errors[] = self::ERR_INVALID_REGION;
            return;
        }

        return $region;
    }
}
