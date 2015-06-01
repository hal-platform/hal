<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use QL\Hal\Core\Entity\User;
use QL\Kraken\Diff;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Core\Type\EnumType\PropertyEnum;
use QL\Kraken\Service\PermissionService;
use QL\Panthor\Utility\Json;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

class KrakenTwigExtension extends Twig_Extension
{
    const NAME = 'kraken';
    const INVALID_DECODED_PROPERTY = 0xA9E1B2E76;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param PermissionService $permissions
     * @param Json $json
     */
    public function __construct(PermissionService $permissions, Json $json)
    {
        $this->permissions = $permissions;
        $this->json = $json;
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
     * @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatSchemaType', [$this, 'formatSchemaType']),
            new Twig_SimpleFilter('formatPropertyValue', [$this, 'formatPropertyValue'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('canUserReleaseTheKraken', [$this, 'canUserDeploy']),
        ];
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return [
            new Twig_SimpleTest('property', function ($entity) {
                return $entity instanceof Property;
            }),
            new Twig_SimpleTest('schema', function ($entity) {
                return $entity instanceof Schema;
            }),
            new Twig_SimpleTest('target', function ($entity) {
                return $entity instanceof Target;
            }),
            new Twig_SimpleTest('snapshot', function ($entity) {
                return $entity instanceof Snapshot;
            }),
            new Twig_SimpleTest('diff', function ($entity) {
                return $entity instanceof Diff;
            }),
            new Twig_SimpleTest('invalidProperty', function ($value) {
                return $value === self::INVALID_DECODED_PROPERTY;
            })
        ];
    }

    /**
     * @param User|null $user
     * @param Application|null $application
     * @param Environment|null $application
     *
     * @return bool
     */
    public function canUserDeploy($user, $application, $environment)
    {
        if (!$user instanceof User) return false;
        if (!$application instanceof Application) return false;
        if (!$environment instanceof Environment) return false;

        return $this->permissions->canUserDeploy($user, $application, $environment);
    }

    /**
     * Format a property schema data type for display
     *
     * @param Schema|Snapshot|Diff|string|null $schema
     *
     * @return string
     */
    public function formatSchemaType($schema = null)
    {
        if ($schema instanceof Diff) {
            $schema = $schema->schema();
        }

        if ($schema instanceof Schema || $schema instanceof Snapshot) {
            $schema = $schema->dataType();
        } elseif (!is_string($schema)) {
            $schema = '???';
        }

        if ($schema) {
            $types = PropertyEnum::map();

            if (isset($types[$schema])) {
                return $types[$schema];
            }
        }

        return 'Unknown';
    }

    /**
     * Format a property value for display
     *
     * @param Snapshot|Property|Diff|null $schema
     * @param int $maxLength
     *
     * @return string|null
     */
    public function formatPropertyValue($property, $maxLength = 100)
    {
        $maxLength = (int) $maxLength;

        if ($property instanceof Diff) {
            $property = $property->property();
        }

        if (!$property instanceof Property && !$property instanceof Snapshot) {
            return '';
        }

        if ($property instanceof Property && $property->schema()->isSecure()) {
            return null;
        }

        if ($property instanceof Snapshot && $property->isSecure()) {
            return null;
        }

        $value = $this->json->decode($property->value());

        if ($value === null) {
            return self::INVALID_DECODED_PROPERTY;
        }

        if (is_array($value)) {
            # W. T. F. - Handle "strings"

            if ($maxLength === 0) {
                $normalized = $value;
                goto SKIP_NORMALIZE;
            }

            $normalized = [];
            $total = 0;
            foreach ($value as $num => $item) {
                $total += mb_strlen($item);
                if ($total > $maxLength) {
                    $normalized[] = sprintf('...%d more items', count($value) - $num);
                    break;
                } else {
                    $normalized[] = $item;
                }
            }

            SKIP_NORMALIZE:
            return $normalized;

        } elseif (is_string($value)) {
            if ($maxLength > 0) {
                $len = mb_strlen($value);
                if ($len > $maxLength) {
                    $value = substr($value, 0, $maxLength) . "...";
                }
            }

        } elseif (is_bool($value)) {
            return ($value) ? 'true' : 'false';

        } elseif (is_scalar($value)) {
            return (string) $value;
        }

        return $value;
    }
}
