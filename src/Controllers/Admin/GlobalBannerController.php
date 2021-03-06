<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\System\GlobalBannerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class GlobalBannerController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Global Banner saved.';
    private const MSG_REMOVED = 'Global Banner removed.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var GlobalBannerService
     */
    private $bannerService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param GlobalBannerService $bannerService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        GlobalBannerService $bannerService,
        URI $uri
    ) {
        $this->template = $template;
        $this->bannerService = $bannerService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($msg = $this->handleForm($request)) {
            $this->withFlashSuccess($request, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'admin.global_banner');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'setting' => $this->bannerService->loadBannerDetails(),
            'update_notification' => $this->bannerService->isUpdateNotificationEnabled(),
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function handleForm(ServerRequestInterface $request): string
    {
        if ($request->getMethod() !== 'POST') {
            return '';
        }

        if (!$this->isCSRFValid($request)) {
            return '';
        }

        $message = $request->getParsedBody()['message'] ?? '';
        $ttl = $request->getParsedBody()['ttl'] ?? '';
        $updateTick = $request->getParsedBody()['update_notification'] ?? '';

        $remove = $request->getParsedBody()['remove'] ?? '';

        $this->saveUpdateTick($updateTick);

        if ($message) {
            $this->bannerService->saveBanner($message, (int) $ttl);
            return self::MSG_SUCCESS;
        }

        if ($remove) {
            $this->bannerService->clearBanner();
            return self::MSG_REMOVED;
        }

        return '';
    }

    /**
     * @param bool $tickOn
     *
     * @return void
     */
    private function saveUpdateTick($tickOn)
    {
        if ($tickOn) {
            $this->bannerService->enableUpdateNotification();
        } else {
            $this->bannerService->disableUpdateNotification();
        }
    }
}
