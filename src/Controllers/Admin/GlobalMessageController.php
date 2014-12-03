<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GlobalMessageService;
use QL\Hal\Session;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class GlobalMessageController
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
     * @param TemplateInterface $template
     * @param GlobalMessageService $messageService
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        TemplateInterface $template,
        GlobalMessageService $messageService,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->messageService = $messageService;
        $this->session = $session;
        $this->url = $url;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        if ($request->isPost() && $request->post('message')) {

            $message = $request->post('message');
            $this->messageService->save($message, (int) $request->post('ttl'));

            $this->session->flash('Global Message saved.', 'success');
            $this->url->redirectFor('admin.super.message');
        }

        $context = [
            'message' => $this->messageService->load(),
            'ttl' => $this->messageService->expiry()
        ];

        $rendered = $this->template->render($context);

        $response->setBody($rendered);
    }
}
