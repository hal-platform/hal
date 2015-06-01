<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Super;

use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Service\GlobalMessageService;
use QL\Hal\Session;
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
     * @type Session
     */
    private $session;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param TemplateInterface $template
     * @param GlobalMessageService $messageService
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        GlobalMessageService $messageService,
        Session $session,
        UrlHelper $url,
        Request $request
    ) {
        $this->template = $template;
        $this->messageService = $messageService;
        $this->session = $session;
        $this->url = $url;

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

                $this->session->flash('Global Message saved.', 'success');
                $this->url->redirectFor('admin.super.message');
                return;

            } elseif ($this->request->post('remove')) {
                $this->messageService->clear();

                $this->session->flash('Global Message removed.', 'success');
                $this->url->redirectFor('admin.super.message');
                return;
            }
        }

        $context = [
            'message' => $this->messageService->load(),
            'ttl' => $this->messageService->expiry()
        ];

        $this->template->render($context);
    }
}
