<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Twig_Extension;
use Twig_Extension_GlobalsInterface;

/**
 * Twig Extension for declaring and preparing global variables
 */
class GlobalExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    const NAME = 'hal_global';

    /**
     * @var array
     */
    private $globals;

    /**
     * @param array $globals
     */
    public function __construct(array $globals = [])
    {
        $this->globals = $globals;
    }

    /**
     * Get the extension name
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getGlobals()
    {
        return $this->globals;
    }
}
