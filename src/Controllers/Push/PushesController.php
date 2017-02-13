<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class PushesController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    const REGEX_ENV = '/(?:environment|env|e):([a-zA-Z-]+)/';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Request
     */
    private $request;

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
     * @param Application $application
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->pushRepo = $em->getRepository(Push::class);
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->application = $application;
        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;
        $searchFilter = is_string($this->request->get('search')) ? $this->request->get('search') : '';

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        if ($environment = $this->getEnvironmentFromSearchFilter($searchFilter)) {
            $sanitizedSearchFilter = trim(preg_replace(self::REGEX_ENV, '', $searchFilter, 1));

            $pushes = $this->pushRepo->getByApplicationForEnvironment($this->application, $environment, self::MAX_PER_PAGE, ($page-1), $sanitizedSearchFilter);
        } else {
            $pushes = $this->pushRepo->getByApplication($this->application, self::MAX_PER_PAGE, ($page-1), $searchFilter);
        }

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        $this->template->render([
            'page' => $page,
            'last' => $last,

            'application' => $this->application,
            'pushes' => $pushes,
            'search_filter' => $searchFilter
        ]);
    }

    /**
     * @param string $search
     *
     * @return Environment|null|false
     */
    private function getEnvironmentFromSearchFilter($search)
    {
        if (preg_match(self::REGEX_ENV, $search, $matches) === 1) {
            $name = strtolower(array_pop($matches));
            return $this->environmentRepo->findOneBy(['name' => $name]);
        }

        return false;
    }
}
