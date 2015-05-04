<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\DoctrineConfigurator as BaseDoctrineConfigurator;

/**
 * Perform runtime configuration of the Doctrine Entity Manager
 */
class DoctrineConfigurator extends BaseDoctrineConfigurator
{
    /**
     * Run the configuration
     *
     * @param EntityManagerInterface $em
     */
    public function configure(EntityManagerInterface $em)
    {
        parent::configure($em);

        $mapping = [
            PropertyEnumType::TYPE => PropertyEnumType::CLASS
        ];

        $platform = $em->getConnection()->getDatabasePlatform();

        foreach ($mapping as $type => $fullyQualified) {
            Type::addType($type, $fullyQualified);
            $platform->registerDoctrineTypeMapping(sprintf('db_%s', $type), $type);
        }
    }
}
