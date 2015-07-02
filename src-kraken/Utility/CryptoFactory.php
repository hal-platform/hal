<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Utility;

use GuzzleHttp\ClientInterface as Guzzle;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Service\Exception\ConfigurationException;
use MCP\Crypto\Primitive\Factory;
use MCP\Crypto\Exception\CryptoException;
use MCP\Crypto\Package\QuickenMessagePackage;
use MCP\Crypto\Package\TamperResistantPackage;
use MCP\DataType\HttpUrl;
use MCP\QKS\Client\HttpClient as QKSGuzzleService;
use MCP\QKS\Client\Parser\JsonParser;

class CryptoFactory
{
    const ERR_EMPTY_SYMMETRIC_KEY = 'Symmetric key file is empty.';
    const ERR_MISSING_SYMMETRIC_KEY = 'Path to symmetric key is invalid.';

    const ERR_MISSING_QKS_SERVICE = 'QKS service is missing for this environment.';
    const ERR_MISSING_QKS_KEY = 'QKS encryption key is missing for this environment.';
    const ERR_MISSING_QKS_AUTH = 'QKS credentials are missing for this environment.';
    const ERR_INVALID_CLIENT_SECRET = 'QKS Secret could not be decrypted for usage.';

    /**
     * @type Guzzle
     */
    private $guzzle;

    /**
     * @type JsonParser
     */
    private $parser;

    /**
     * @type string
     */
    private $symKeyPath;

    /**
     * @type callable
     */
    private $fileLoader;

    /**
     * @param Guzzle $guzzle
     * @param JsonParser $parser
     *
     * @param string $symKeyPath
     * @param callable|null $fileLoader
     */
    public function __construct(
        Guzzle $guzzle,
        JsonParser $parser,
        $symKeyPath,
        callable $fileLoader = null
    ) {
        // qmp
        $this->guzzle = $guzzle;
        $this->parser = $parser;

        // trp
        $this->symKeyPath = $symKeyPath;
        $this->fileLoader = $fileLoader ?: [$this, 'defaultFileLoader'];
    }

    /**
     * @throws ConfigurationException
     *
     * @return TamperResistantPackage
     */
    public function getTRP()
    {
        $key = call_user_func($this->fileLoader, $this->symKeyPath);
        return new TamperResistantPackage(new Factory, $key);
    }

    /**
     * @param Environment $environment
     *
     * @throws ConfigurationException
     *
     * @return QuickenMessagePackage
     */
    public function getQMP(Environment $environment)
    {
        $qksHost = $environment->qksServiceURL();
        $qksKey = $environment->qksEncryptionKey();
        $clientID = $environment->qksClientID();
        $encryptedClientSecret = $environment->qksClientSecret();

        if (!$qksHost) {
            throw new ConfigurationException(self::ERR_MISSING_QKS_SERVICE);
        }

        if (!$qksKey) {
            throw new ConfigurationException(self::ERR_MISSING_QKS_KEY);
        }

        if (!$clientID || !$encryptedClientSecret) {
            throw new ConfigurationException(self::ERR_MISSING_QKS_AUTH);
        }

        $trp = $this->getTRP();

        if (!$clientSecret = $this->decrypt($trp, $encryptedClientSecret)) {
            throw new ConfigurationException(self::ERR_INVALID_CLIENT_SECRET);
        }

        // bullshit
        $qksHost = HttpUrl::create($qksHost);
        $qksHost = $qksHost->host();

        $service = new QKSGuzzleService($this->guzzle, $this->parser, [
            QKSGuzzleService::CONFIG_HOSTNAME => $qksHost,
            QKSGuzzleService::CONFIG_CLIENT_ID => $clientID,
            QKSGuzzleService::CONFIG_CLIENT_SECRET => $clientSecret,
        ]);

        return new QuickenMessagePackage(new Factory, $service, $qksKey);
    }

    /**
     * @param string $path
     * @return callable
     */
    protected function defaultFileLoader($path)
    {
        if (!file_exists($path)) {
            throw new ConfigurationException(self::ERR_MISSING_SYMMETRIC_KEY);
        }

        $file = file_get_contents($path);
        $file = trim($file);

        if (!$file) {
            throw new ConfigurationException(self::ERR_EMPTY_SYMMETRIC_KEY);
        }

        return $file;
    }

    /**
     * @param TamperResistantPackage $trp
     * @param string $encrypted
     *
     * @return string|null
     */
    private function decrypt(TamperResistantPackage $trp, $encrypted)
    {
        try {
            $decrypted = $trp->decrypt($encrypted);
        } catch (CryptoException $ex) {
            $decrypted = null;
        }

        return $decrypted;
    }
}
