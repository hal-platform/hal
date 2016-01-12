<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Application;

/**
 * String utilities for transforming config parameters in the symfony container
 */
class Stringify
{
    /**
     * Concatenate a bunch of strings together.
     *
     * This is simply used to dynamically construct synthetic services that are really just parameters
     * but Symfony limitations prevent setting those after the container is frozen.
     */
    public static function smush()
    {
        $args = func_get_args();
        return implode('', $args);
    }

    /**
     * Strips all of the newlines, tabs, and spaces out of an encrypted key, so we can store it in the config without it looking like poo.
     *
     * @param string $data
     * @return string
     */
    public static function squish($data)
    {
        return str_replace(["\n", "\t", ''], '', $data);
    }
}
