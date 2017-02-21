<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Utility;

use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use QL\Hal\Core\Entity\User;
use QL\Panthor\Twig\Context;

/**
 * This sucks.
 *
 * This requires the UserSessionGlobalMiddleware to be run which populates the user into the template context.
 *
 * This means only required-signed-in pages will make the user available, and pages
 * cannot run in a dual signed-out/signed-in mode.
 *
 * @todo find a better way to pass user to logger with hal-core 3.0
 */
class LazyUserRetriever
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return User|null
     */
    public function __invoke(): ?User
    {
        $user = $this->context->get(UserSessionGlobalMiddleware::USER_ATTRIBUTE);

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
