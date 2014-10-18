<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;


/**
 * Add runtime services to the twig environment.
 *
 * This hook should be attached to the "slim.before.dispatch" event.
 */
class TwigEnvironmentHook
{
    public function __construct(

    ) {

    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $this->environment->addGlobal('session', $this->session);
        $this->environment->addGlobal('account', $this->session->get('account'));
        $this->environment->addGlobal('isFirstLogin', $this->session->get('isFirstLogin'));

        $this->environment->addGlobal('app_title', $this->appTitle);
    }
}
