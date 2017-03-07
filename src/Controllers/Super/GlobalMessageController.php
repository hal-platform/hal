<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Super;

use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\GlobalMessageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class GlobalMessageController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Global Message saved.';
    private const MSG_REMOVED = 'Global Message removed.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var GlobalMessageService
     */
    private $messageService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param GlobalMessageService $messageService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        GlobalMessageService $messageService,
        URI $uri
    ) {
        $this->template = $template;
        $this->messageService = $messageService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() === 'POST') {

            $message = $request->getParsedBody()['message'] ?? '';
            $remove = $request->getParsedBody()['remove'] ?? '';
            $ttl = $request->getParsedBody()['ttl'] ?? '';
            $updateTick = $request->getParsedBody()['update-tick'] ?? '';

            if ($message) {
                $this->messageService->save($message, (int) $ttl);
                $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
                return $this->withRedirectRoute($response, $this->uri, 'super.message');

            } elseif ($remove) {
                $this->messageService->clear();
                $this->withFlash($request, Flash::SUCCESS, self::MSG_REMOVED);
                return $this->withRedirectRoute($response, $this->uri, 'super.message');
            }

            if ($updateTick) {
                $this->messageService->enableUpdateTick();
            } else {
                $this->messageService->clearUpdateTick();
            }
        }

        return $this->withTemplate($request, $response, $this->template, [
            'message' => $this->messageService->load(),
            'ttl' => $this->messageService->expiry()
        ]);
    }
}
