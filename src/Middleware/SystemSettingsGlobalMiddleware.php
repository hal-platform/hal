<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\System\GlobalBannerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;

/**
 * Loads the global system settings from the DB (or cache)
 * - Request (attribute: global.banner, global.update_notification)
 * - Template Context (variable: global.banner, global.update_notification)
 */
class SystemSettingsGlobalMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    /**
     * @var GlobalBannerService
     */
    private $bannerService;

    /**
     * @param GlobalBannerService $bannerService
     */
    public function __construct(GlobalBannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $notificationEnabled = $this->bannerService->isUpdateNotificationEnabled();
        $banner = $this->bannerService->fetchBanner();

        $context = [
            GlobalBannerService::NAME_NOTIFICATION => $notificationEnabled,
            GlobalBannerService::NAME_BANNER => $banner,
        ];

        // attach to request
        $request = $this
            ->withContext($request, $context)
            ->withAttribute(GlobalBannerService::NAME_NOTIFICATION, $notificationEnabled)
            ->withAttribute(GlobalBannerService::NAME_BANNER, $banner);

        return $next($request, $response);
    }
}
