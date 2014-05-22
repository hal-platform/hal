<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class EditHandle
{
    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     *  @param UserRepository $userRepo
     *  @param UrlHelper $url
     */
    public function __construct(UserRepository $userRepo, UrlHelper $url)
    {
        $this->userRepo = $userRepo;
        $this->url = $url;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];
        if (!$user = $this->userRepo->findOneBy(['id' => $id])) {
            return call_user_func($notFound);
        }

        $this->url->redirectFor('denied');
    }
}
