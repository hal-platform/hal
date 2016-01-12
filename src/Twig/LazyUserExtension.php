<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Twig;

use Exception;
use QL\Hal\Core\Entity\User;
use QL\Hal\Session;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;
use Twig_SimpleFunction;

class LazyUserExtension extends Twig_Extension
{
    const NAME = 'hal_lazy';

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var Session
     */
    private $session;

    /**
     * Local storage of user, to prevent multiple hits to DB while template is rendering.
     *
     * @var User|null|false
     */
    private $user;

    /**
     * @param ContainerInterface $di
     * @param Session $session
     */
    public function __construct(ContainerInterface $di, Session $session)
    {
        $this->di = $di;
        $this->session = $session;
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
     * Get an array of Twig Functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('getCurrentUser', [$this, 'getCurrentUser']),
        ];
    }

    /**
     * This is required because we need to force the user to load, if the user is available.
     *
     * The "lazy user" loader used by the doctrine change logger fails gracefully if no user is available, it does not force a user load.
     *
     * @return User|null
     */
    public function getCurrentUser()
    {
        if ($this->user) {
            return $this->user;
        }

        // Previous request failed, dont try again
        if ($this->user === false) {
            return null;
        }

        // User entity was loaded - usually from LoginMiddleware
        if ($user = $this->session->user()) {
            return $user;
        }

        // Try lazy loading user if never loaded
        if ($user = $this->lazyLoad()) {
            $this->user = $user;
        } else {
            // Couldn't load, save as false
            $this->user = false;
        }

        return $user;
    }

    /**
     * @return User|null
     */
    private function lazyLoad()
    {
        $user = null;

        $userID = $this->session->get('user_id');
        try {
            $user = $this->di
                ->get('doctrine.em')
                ->find(User::CLASS, $userID);
        } catch (Exception $ex) {
            $user = null;
        }

        return $user;
    }
}
