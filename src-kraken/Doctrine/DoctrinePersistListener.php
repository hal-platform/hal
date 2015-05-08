<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use MCP\DataType\Time\Clock;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;

/**
 * A doctrine event listener for:
 * - Add a "created" TimePoint to persisted objects when initially created.
 *     - Configuration
 *     - ConfigurationProperty
 *     - Property
 *     - Schema
 *
 * It should be attached to the PrePersist event.
 *
 * Default timestamps are done through code and not the database so we can maintain timezone consistency.
 */
class DoctrinePersistListener
{
    /**
     * @type Clock
     */
    private $clock;

    /**
     * @param Clock $clock
     */
    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    /**
     * Listen for Doctrine prePersist events.
     *
     * Ensure that entities have a "Created Time" when they are created.
     *
     * @param EventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        // Add created time
        if ($this->isTimestampable($entity)) {
            if (!is_callable([$entity, 'created']) || !is_callable([$entity, 'withCreated'])) {
                return;
            }

            if (!$entity->created()) {
                $created = $this->clock->read();
                $entity->withCreated($created);
            }
        }
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    private function isTimestampable($entity)
    {
        if ($entity instanceof Configuration) return true;
        if ($entity instanceof ConfigurationProperty) return true;
        if ($entity instanceof Property) return true;
        if ($entity instanceof Schema) return true;

        return false;
    }
}
