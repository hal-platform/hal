<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RollbackController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     *  @param TemplateInterface $template
     *  @param RepositoryRepository $repoRepository
     *  @param ServerRepository $serverRepository
     *  @param PushRepository $pushRepository
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepository,
        ServerRepository $serverRepository,
        PushRepository $pushRepository
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepository;
        $this->serverRepo = $serverRepository;
        $this->pushRepo = $pushRepository;
    }

    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->find($params['id']);
        $server = $this->serverRepo->find($params['server']);

        if (!$repo || !$server) {
            return $notFound();
        }

        $pushes = $this->pushRepo->getAvailableRollbacks($repo, $server);

        $rendered = $this->template->render([
            'repo' => $repo,
            'server' => $server,
            'pushes' => $pushes
        ]);

        $response->setBody($rendered);
    }
}
