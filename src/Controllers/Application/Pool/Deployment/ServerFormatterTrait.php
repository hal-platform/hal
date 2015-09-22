<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool\Deployment;

use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;

class ServerFormatterTrait
{
    /**
     * @param Server $server
     *
     * @return string
     */
    public function formatServerType(Server $server)
    {
        if ($server->type() === ServerEnum::TYPE_EB) {
            $name = 'Elastic Beanstalk';
        } elseif ($server->type() === ServerEnum::TYPE_EC2) {
            $name = 'EC2';
        } elseif ($server->type() === ServerEnum::TYPE_S3) {
            $name = 'S3';
        } elseif ($server->type() === ServerEnum::TYPE_CD) {
            $name = 'CD';
        } else {
            $name = $server->name();
        }
    }
}
