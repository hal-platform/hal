<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Twig;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * YOU get an extension and YOU get an extension
 *
 * EVERYBODY GETS AN EXTENSION!
 *
 * An extension to format statuses like build/push status, EB health status, etc with pretty icons and shit.
 */
class StatusExtension extends Twig_Extension
{
    const NAME = 'hal_status';

    const HTML_STATUS_TEMPLATE = '<span class="%s">%s</span>';

    /**
     *  Get the extension name
     *
     *  @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     *  Get an array of Twig Filters
     *
     *  @return array
     */
    public function getFilters()
    {
        return [
            // build/push
            new Twig_SimpleFilter('formatBuildStatus', [$this, 'stylizeBuildStatus'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('formatPushStatus', [$this, 'stylizePushStatus'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @param Build|null $build
     *
     * @return string
     */
    public function stylizeBuildStatus($build)
    {
        $default = sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--info', 'Unknown');

        if (!$build instanceof Build) {
            return $default;
        }

        if ($build->status() === 'Success') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--success', $build->status());

        } elseif (in_array($build->status(), ['Error', 'Removed'], true)) {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--error', $build->status());

        } elseif ($build->status()) {
            return sprintf('<span class="%s" data-build="%s">%s</span>', 'status-icon--warning', $build->id(), $build->status());
        }

        return $default;
    }

    /**
     * @param Push|null $push
     *
     * @return string
     */
    public function stylizePushStatus($push)
    {
        $default = sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--info', 'Unknown');

        if (!$push instanceof Push) {
            return $default;
        }

        if ($push->status() === 'Success') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--success', $push->status());

        } elseif ($push->status() === 'Error') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--error', $push->status());

        } elseif ($push->status()) {
            return sprintf('<span class="%s" data-push="%s">%s</span>', 'status-icon--warning', $push->id(), $push->status());
        }

        return $default;
    }
}
