<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EnvironmentController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $envRepo;
    private $serverRepo;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$environment = $this->envRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $servers = $this->serverRepo->findBy(['environment' => $environment]);
        usort($servers, $this->serverSorter());

        $this->template->render([
            'env' => $environment,
            'servers' => $servers
        ]);
    }
}
