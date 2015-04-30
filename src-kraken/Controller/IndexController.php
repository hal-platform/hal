<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManager;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class IndexController implements ControllerInterface
{
    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param $em
     */
    public function __construct(Request $request, TemplateInterface $template, $em)
    {
        $this->request = $request;
        $this->template = $template;
        $this->em = $em;
    }

    /**
     * @return null
     */
    public function __invoke()
    {

        $items = [];

        $context = [
            'test' => $items
        ];

        $this->template->render($context);
    }
}
