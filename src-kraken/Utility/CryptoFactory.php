<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Utility;

use MCP\Crypto\Exception\CryptoException;
use MCP\Crypto\Package\QuickenMessagePackage;
use MCP\Crypto\Package\TamperResistantPackage;
use MCP\Crypto\Primitive\Factory as PrimitiveFactory;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Service\Exception\ConfigurationException;

class CryptoFactory
{
    const ERR_EMPTY_SYMMETRIC_KEY = 'Symmetric key file is empty.';
    const ERR_MISSING_SYMMETRIC_KEY = 'Path to symmetric key is invalid.';

    const ERR_MISSING_QKS_SERVICE = 'QKS service is missing for this environment.';
    const ERR_MISSING_QKS_KEY = 'QKS encryption key is missing for this environment.';
    const ERR_MISSING_QKS_AUTH = 'QKS credentials are missing for this environment.';
    const ERR_INVALID_CLIENT_SECRET = 'QKS Secret could not be decrypted for usage.';
    const ERR_QKS_EXPLODED = 'QKS Service Client could not be built.';

    /**
     * @type QKSFactory
     */
    private $qksFactory;

    /**
     * @type string
     */
    private $symKeyPath;

    /**
     * @type callable
     */
    private $fileLoader;

    /**
     * @param QKSFactory $qksFactory
     * @param string $symKeyPath
     * @param callable|null $fileLoader
     */
    public function __construct(QKSFactory $qksFactory, $symKeyPath, callable $fileLoader = null)
    {
        $this->qksFactory = $qksFactory;

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
        return new TamperResistantPackage(new PrimitiveFactory, $key);
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
        $qksURL = $environment->qksServiceURL();
        $qksSendingKey = $environment->qksEncryptionKey();
        $clientID = $environment->qksClientID();
        $encryptedClientSecret = $environment->qksClientSecret();

        if (!$qksURL) {
            throw new ConfigurationException(self::ERR_MISSING_QKS_SERVICE);
        }

        if (!$qksSendingKey) {
            throw new ConfigurationException(self::ERR_MISSING_QKS_KEY);
        }

        if (!$clientID || !$encryptedClientSecret) {
            throw new ConfigurationException(self::ERR_MISSING_QKS_AUTH);
        }

        $trp = $this->getTRP();

        if (!$clientSecret = $this->decrypt($trp, $encryptedClientSecret)) {
            throw new ConfigurationException(self::ERR_INVALID_CLIENT_SECRET);
        }

        if ($qmp = $this->qksFactory->getQMP($qksURL, $clientID, $clientSecret, $qksSendingKey)) {
            return $qmp;
        }

        throw new ConfigurationException(self::ERR_QKS_EXPLODED);
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
