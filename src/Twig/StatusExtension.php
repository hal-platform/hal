<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Services\ElasticBeanstalk\Environment as EBEnvironment;
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
            new Twig_SimpleFilter('formatPushStatus', [$this, 'stylizePushStatus'], ['is_safe' => ['html']]),

            // elastic beanstalk
            new Twig_SimpleFilter('formatEBHealth', [$this, 'stylizeEBHealth'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('formatEBStatus', [$this, 'stylizeEBStatus'], ['is_safe' => ['html']])
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

    /**
     * @param EBEnvironment|null $environment
     *
     * @return string
     */
    public function stylizeEBHealth($environment)
    {
        $default = sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--info', 'Unknown');

        if (!$environment instanceof EBEnvironment) {
            return $default;
        }

        if ($environment->health() === 'Green') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--success', $environment->health());

        } elseif ($environment->health() === 'Red') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--error', $environment->health());

        } elseif ($environment->health() === 'Yellow') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--other', $environment->health());
        }

        return $default;
    }

    /**
     * @param EBEnvironment|null $environment
     *
     * @return string
     */
    public function stylizeEBStatus($environment)
    {
        $default = sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--info', 'Unknown');

        if (!$environment instanceof EBEnvironment) {
            return $default;
        }

        if ($environment->status() === 'Ready') {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--success', $environment->status());

        } elseif (in_array($environment->status(), ['Terminating', 'Terminated'], true)) {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--error', $environment->status());

        } elseif (in_array($environment->status(), ['Launching', 'Updating'], true)) {
            return sprintf(self::HTML_STATUS_TEMPLATE, 'status-icon--warning', $environment->status());
        }

        return $default;
    }
}
