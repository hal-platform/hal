<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Super;

use QL\Hal\Service\GlobalMessageService;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class GlobalMessageController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type GlobalMessageService
     */
    private $messageService;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param TemplateInterface $template
     * @param GlobalMessageService $messageService
     * @param Flasher $flasher
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        GlobalMessageService $messageService,
        Flasher $flasher,
        Request $request
    ) {
        $this->template = $template;
        $this->messageService = $messageService;
        $this->flasher = $flasher;

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {

            if ($this->request->post('message')) {

                $message = $this->request->post('message');
                $this->messageService->save($message, (int) $this->request->post('ttl'));

                return $this->flasher
                    ->withFlash('Global Message saved.', 'success')
                    ->load('admin.super.message');

            } elseif ($this->request->post('remove')) {
                $this->messageService->clear();

                return $this->flasher
                    ->withFlash('Global Message removed.', 'success')
                    ->load('admin.super.message');
            }

            if ($this->request->post('update-tick')) {
                $this->messageService->enableUpdateTick();
            } else {
                $this->messageService->clearUpdateTick();
            }
        }

        $context = [
            'message' => $this->messageService->load(),
            'ttl' => $this->messageService->expiry()
        ];

        $this->template->render($context);
    }
}
