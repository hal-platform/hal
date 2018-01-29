<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Hal\UI\Security\CSRFManager;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class SecurityExtension extends AbstractExtension
{
    /**
     * @var CSRFManager
     */
    private $csrf;

    /**
     * @param CSRFManager $csrf
     */
    public function __construct(CSRFManager $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * Get an array of Twig Functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('csrf_token', [$this, 'generateCSRFToken']),
        ];
    }

    /**
     * @param string $form
     *
     * @return string
     */
    public function generateCSRFToken($form)
    {
        $csrf = $this->csrf->generateToken($form);

        return $csrf;
    }
}
