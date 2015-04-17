<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Utility;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class DiArrayUtil
{
    public static function indexed()
    {
        $new = [];

        foreach (func_get_args() as $param) {
            $new[] = $param;
        }

        return $new;
    }

    public static function associative()
    {
        $new = [];

        foreach (func_get_args() as $param) {
            if (is_array($param) && isset($param['key']) && isset($param['value'])) {
                $new[$param['key']] = $param['value'];
            }
        }

        return $new;
    }
}
