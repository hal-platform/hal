<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Utility;

use QL\Hal\Core\Entity\User;

class NameFormatter
{
    /**
     * @param User $user
     *
     * @return string
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
     * Get the user's first name
     *
     * @param User $user
     *
     * @return string
     */
    public function getUsersFirstName($user)
    {
        $name = $this->getUsersName($user);

        return trim(strstr($name, ' ', true));
    }

    /**
     * Get the user's name
     *
     * @param User $user
     *
     * @return string
     */
    public function getUsersName($user)
    {
        $name = '';
        if (!$user instanceof User) {
            return $name;
        }

        $exploded = explode(',', $user->name());
        if (count($exploded) === 2) {
            $name = sprintf('%s %s', trim($exploded[1]), trim($exploded[0]));
        } else {
            $name = $user->name();
        }

        return trim($name);
    }

    /**
     * Get the user's freudian name
     *
     * @see http://tvtropes.org/pmwiki/pmwiki.php/Main/CallAHumanAMeatbag
     *
     * @return string
     */
    public function getUsersFreudianName()
    {
        $potential = [
            'earth germ',
            'human',
            'meatbag',
            'mission compromiser',
            'mortal',
            'organic',
            'puny earth creature',
            'squishy',
            'threat to the mission',
            'Mr. Langley',
            'daisy',
            'pawn',
            'test subject #1498'
        ];

        shuffle($potential);
        return array_pop($potential);
    }
}
