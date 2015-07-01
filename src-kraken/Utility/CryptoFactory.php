<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Utility;

use QL\Hal\Core\Crypto\CryptoException;
use MCP\Crypto\Primitive\Factory;
use MCP\Crypto\Package\TamperResistantPackage;

class CryptoFactory
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
     * @return TamperResistantPackage
     */
    public function getPackager()
    {
        if (!file_exists($this->symKeyPath)) {
            throw new CryptoException('Path to symmetric key is invalid.');
        }

        $key = call_user_func($this->fileLoader, $this->symKeyPath);
        return new TamperResistantPackage(new Factory, trim($key));
    }

    /**
     * @return callable
     */
    protected function getDefaultFileLoader()
    {
        return 'file_get_contents';
    }
}
