<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\User as DomainUser;

class NameHelper
{
    /**
     *  Get the user's actual name
     *
     *  @param LdapUser|DomainUser $user
     *  @return string
     */
    public function getUsersActualName($user)
    {
        $name = $this->getUsersFirstName($user);

        if (preg_match('/(Dave|David)/', $name) === 1) {
            return 'Frank';
        }

        return 'Dave';
    }

    /**
     *  Get the user's first name
     *
     *  @param LdapUser|DomainUser $user
     *  @return string
     */
    public function getUsersFirstName($user)
    {
        $name = $this->getUsersName($user);

        return strstr($name, ' ');
    }

    /**
     *  Get the user's name
     *
     *  @param LdapUser|DomainUser $user
     *  @return string
     */
    public function getUsersName($user)
    {
        $name = '';
        if ($user instanceof LdapUser) {
            $name = sprintf('%s %s', $user->firstName(), $user->lastName());

        } elseif ($user instanceof DomainUser) {
            $exploded = explode(',', $user->getName());
            if ($exploded === 2) {
                $name = sprintf('%s %s', $exploded[1], $exploded[0]);
            } else {
                $name = $user->getName();
            }
        }

        return trim($name);
    }

    /**
     *  Get the user's freudian name
     *
     * @see http://tvtropes.org/pmwiki/pmwiki.php/Main/CallAHumanAMeatbag
     *
     *  @return string
     */
    public function getUsersFreudianName()
    {
        $potential = [
            'meatbag',
            'puny earth creature',
            'mortal',
            'human',
            'organic',
            'organic battery',
            'mission compromiser',
            'threat to the mission',
            'buzzkill',
        ];

        shuffle($potential);
        return array_pop($potential);
    }
}
