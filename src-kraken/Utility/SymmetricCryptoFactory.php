<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Utility;

use QL\Hal\Core\Crypto\CryptoException;
use QL\Hal\Core\Crypto\SymmetricDecrypter;
use QL\Hal\Core\Crypto\SymmetricEncrypter;

class SymmetricCryptoFactory
{
    /**
     * @type string
     */
    private $symKeyPath;

    /**
     * @type callable
     */
    private $fileLoader;

    /**
     * @param string $symKeyPath
     * @param callable|null $fileLoader
     */
    public function __construct($symKeyPath, callable $fileLoader = null)
    {
        $this->symKeyPath = $symKeyPath;
        $this->fileLoader = $fileLoader ?: $this->getDefaultFileLoader();
    }

    /**
     * @throws CryptoException
     *
     * @return SymmetricEncrypter
     */
    public function getSymmetricEncrypter()
    {
        if (!file_exists($this->symKeyPath)) {
            throw new CryptoException('Path to symmetric key is invalid.');
        }

        $key = call_user_func($this->fileLoader, $this->symKeyPath);
        return new SymmetricEncrypter($key);
    }

    /**
     * @throws CryptoException
     *
     * @return SymmetricDecrypter
     */
    public function getSymmetricDecrypter()
    {
        if (!file_exists($this->symKeyPath)) {
            throw new CryptoException('Path to symmetric key is invalid.');
        }

        $key = call_user_func($this->fileLoader, $this->symKeyPath);
        return new SymmetricDecrypter($key);
    }

    /**
     * @return callable
     */
    protected function getDefaultFileLoader()
    {
        return 'file_get_contents';
    }
}
