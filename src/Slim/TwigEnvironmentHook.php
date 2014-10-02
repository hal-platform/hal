<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use QL\Hal\Session;
use Twig_Environment;

/**
 * Add runtime services to the twig environment.
 *
 * This hook should be attached to the "slim.before.dispatch" event.
 */
class TwigEnvironmentHook
{
    /**
     * @var Twig_Environment
     */
    private $environment;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Twig_Environment $environment
     * @param Session $session
     */
    public function __construct(Twig_Environment $environment, Session $session)
    {
        $this->environment = $environment;
        $this->session = $session;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $this->environment->addGlobal('session', $this->session);
        $this->environment->addGlobal('account', $this->session->get('account'));
        $this->environment->addGlobal('isFirstLogin', $this->session->get('isFirstLogin'));
    }
}
