<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use MCP\Logger\MessageFactoryInterface;
use QL\Panthor\Slim\McpLoggerHook as BaseMcpLoggerHook;
use Slim\Slim;

/**
 * Extend the logger hook from panthor to use the "correct" hostname
 */
class McpLoggerHook extends BaseMcpLoggerHook
{
    /**
     * @type MessageFactoryInterface
     */
    private $factory;

    /**
     * @param MessageFactoryInterface $factory
     */
    public function __construct(MessageFactoryInterface $factory)
    {
        $this->factory = $factory;
        parent::__construct($factory);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Slim $slim)
    {
        parent::__invoke($slim);

        $this->factory->setDefaultProperty('machineName', gethostname());
    }
}
